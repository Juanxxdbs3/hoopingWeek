from datetime import datetime, timedelta
from typing import Dict, Any, List, Optional
from app.services.data_layer_client import DataLayerClient
from app.config.settings import settings

# Configuración para buscar huecos libres
SEARCH_STEP_MINUTES = 30
MAX_DAYS_AHEAD = 30

def _parse_dt(s: str) -> datetime:
    """Parsea fechas robustamente (ISO o formato SQL)"""
    try:
        return datetime.strptime(s, "%Y-%m-%d %H:%M:%S")
    except ValueError:
        try:
            return datetime.fromisoformat(s)
        except ValueError:
            raise ValueError(f"Formato fecha no reconocido: {s}")

def _format_dt(dt: datetime) -> str:
    return dt.strftime("%Y-%m-%d %H:%M:%S")

class ApprovalOrchestrator:
    """Orquestador avanzado de aprobaciones con resolución de conflictos"""

    def __init__(self):
        self.data_layer = DataLayerClient()
        self.SUPER_ADMIN_ROLE = settings.ROLE_SUPER_ADMIN_ID
        self.FIELD_MANAGER_ROLE = settings.ROLE_FIELD_MANAGER_ID

    async def _get_user(self, user_id: int) -> Dict[str, Any]:
        return await self.data_layer.get(f"/api/users/{user_id}")

    async def _check_overlap(self, field_id: int, start_iso: str, end_iso: str) -> Dict[str, Any]:
        return await self.data_layer.post("/api/reservations/check-overlap", {
            "field_id": field_id,
            "start_datetime": start_iso,
            "end_datetime": end_iso
        })

    async def _find_next_available_slot(self, field_id: int, duration_hours: float, start_search: datetime) -> Optional[Dict[str, datetime]]:
        """Busca el siguiente hueco disponible para mover una reserva"""
        step = timedelta(minutes=SEARCH_STEP_MINUTES)
        duration_td = timedelta(hours=duration_hours)
        now = start_search
        deadline = start_search + timedelta(days=MAX_DAYS_AHEAD)

        while now <= deadline:
            candidate_start = now
            candidate_end = candidate_start + duration_td
            
            # Verificar si el hueco está libre
            try:
                overlap_resp = await self._check_overlap(field_id, _format_dt(candidate_start), _format_dt(candidate_end))
                
                has_conflict = False
                if overlap_resp.get("ok"):
                    overlap_data = overlap_resp.get("overlap", {})
                    if overlap_data.get("has_conflict"):
                        has_conflict = True
                else:
                    # Si falla la verificación, asumimos conflicto por seguridad
                    has_conflict = True
            except Exception:
                has_conflict = True

            if not has_conflict:
                return {"start": candidate_start, "end": candidate_end}

            now += step
        
        return None

    async def _validate_manager_shift(self, manager_id: int, field_id: int, reservation_start: str) -> bool:
        """Valida si el manager tiene turno activo que cubra el inicio de la reserva"""
        try:
            dt = _parse_dt(reservation_start)
            day_of_week = dt.isoweekday() # 1=Lunes...
            time_str = dt.strftime("%H:%M:%S")

            resp = await self.data_layer.get("/api/manager-shifts", {
                "manager_id": manager_id,
                "field_id": field_id,
                "day_of_week": day_of_week
            })

            if not resp.get("ok"):
                return False

            shifts = resp.get("manager_shifts", {}).get("data", [])
            for shift in shifts:
                if not shift.get("active"): continue
                if shift.get("start_time") <= time_str <= shift.get("end_time"):
                    return True
            return False
        except Exception as e:
            print(f"Error validando turno: {e}")
            return False

    async def approve_reservation(self, reservation_id: int, approver_id: int, note: Optional[str] = None) -> Dict[str, Any]:
        # 1. Obtener reserva
        res_resp = await self.data_layer.get(f"/api/reservations/{reservation_id}")
        if not res_resp.get("ok"):
            return {"ok": False, "message": "Reserva no encontrada", "code": 404}
        reservation = res_resp.get("reservation")

        if reservation.get("status") != "pending":
            return {"ok": False, "message": "Solo se pueden aprobar reservas pendientes", "code": 400}

        # 2. Obtener rol del aprobador
        user_resp = await self._get_user(approver_id)
        if not user_resp.get("ok"):
            return {"ok": False, "message": "Usuario aprobador no encontrado", "code": 404}
        approver_role = user_resp["user"].get("role_id")

        # 3. Validar permisos (Manager Shift)
        if approver_role == self.FIELD_MANAGER_ROLE:
            has_shift = await self._validate_manager_shift(
                approver_id, 
                reservation["field_id"], 
                reservation["start_datetime"]
            )
            if not has_shift:
                return {"ok": False, "message": "No tienes un turno activo para este campo en el horario de la reserva", "code": 403}

        # 4. Lógica de Desplazamiento (Solo para Campeonatos)
        displaced_log = []
        if reservation.get("activity_type") == "match_championship":
            try:
                # Buscar conflictos actuales
                overlap = await self._check_overlap(
                    reservation["field_id"], 
                    reservation["start_datetime"], 
                    reservation["end_datetime"]
                )
                
                conflicts = overlap.get("overlap", {}).get("conflicts", [])
                champ_priority = reservation.get("priority", 1)

                # Filtrar: solo mover reservas con PEOR prioridad (número mayor)
                to_move = [c for c in conflicts if c["id"] != reservation_id and c.get("priority", 99) > champ_priority]

                for conf in to_move:
                    # Calcular duración original
                    start_c = _parse_dt(conf["start_datetime"])
                    end_c = _parse_dt(conf["end_datetime"])
                    duration = (end_c - start_c).total_seconds() / 3600

                    # Buscar nuevo slot
                    new_slot = await self._find_next_available_slot(reservation["field_id"], duration, end_c)
                    
                    if new_slot:
                        # Mover reserva
                        await self.data_layer.put(f"/api/reservations/{conf['id']}", {
                            "start_datetime": _format_dt(new_slot["start"]),
                            "end_datetime": _format_dt(new_slot["end"]),
                            "notes": f"{conf.get('notes', '')} [Desplazada por campeonato ID {reservation_id}]"
                        })
                        displaced_log.append({"id": conf["id"], "new_start": _format_dt(new_slot["start"])})
                    else:
                        # Cancelar si no hay espacio
                        await self.data_layer.patch(f"/api/reservations/{conf['id']}/status", {
                            "status": "cancelled",
                            "cancellation_reason": "Desplazada por campeonato (sin cupo disponible)",
                            "cancelled_by": approver_id
                        })
            except Exception as e:
                print(f"Error en lógica de desplazamiento: {e}")

        # 5. Aprobar la reserva principal
        payload = {
            "status": "approved",
            "approved_by": approver_id,
            "approved_at": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "notes": note or reservation.get("notes")
        }
        
        result = await self.data_layer.patch(f"/api/reservations/{reservation_id}/status", payload)
        
        if result.get("ok"):
            result["displaced_reservations"] = displaced_log
            
        return result

    async def reject_reservation(self, reservation_id: int, approver_id: int, reason: str) -> Dict[str, Any]:
        # Lógica simplificada de rechazo
        payload = {
            "status": "rejected",
            "rejected_by": approver_id,
            "rejection_reason": reason,
            "rejected_at": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        }
        return await self.data_layer.patch(f"/api/reservations/{reservation_id}/status", payload)

    async def cancel_reservation(self, reservation_id: int, user_id: int, reason: str) -> Dict[str, Any]:
        # Lógica simplificada de cancelación
        payload = {
            "status": "cancelled",
            "cancelled_by": user_id,
            "cancellation_reason": reason,
            "cancelled_at": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        }
        return await self.data_layer.patch(f"/api/reservations/{reservation_id}/status", payload)