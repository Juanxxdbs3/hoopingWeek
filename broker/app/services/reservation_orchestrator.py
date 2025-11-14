from datetime import datetime
from typing import Dict, Any, List
from app.services.data_layer_client import DataLayerClient
from app.models.business_rules import BusinessRules
from app.models.schemas import ReservationCreateRequest, ValidationResult
import httpx

class ReservationOrchestrator:
    """Orquestador de reservas con validaciones DS/DCC"""
    
    def __init__(self):
        self.data_layer = DataLayerClient()
        self.rules = BusinessRules()
    
    async def create_with_validation(self, request: ReservationCreateRequest) -> Dict[str, Any]:
        """
        Crea una reserva aplicando validaciones de negocio
        
        Flujo completo:
        1. Validar DS (duración, anticipación)
        2. Validar DCC (fechas especiales)
        3. Validar que el campo existe
        4. Validar participantes existen
        5. Validar restricciones de rol
        6. Validar restricción de field_manager
        7. Detectar conflictos de horario (SOLAPAMIENTO)
        8. Calcular prioridad
        9. Crear en Data Layer
        """
        errors = []
        
        # 1. Validar duración
        valid, msg = self.rules.validate_duration(request.start_datetime, request.end_datetime)
        if not valid:
            errors.append(msg)
        
        # 2. Validar anticipación
        valid, msg = self.rules.validate_advance_time(request.start_datetime)
        if not valid:
            errors.append(msg)
        
        # 3. Validar fecha no bloqueada (DCC)
        blocked, msg = self.rules.is_date_blocked(request.start_datetime)
        if blocked:
            errors.append(msg)
        
        if errors:
            return {"ok": False, "errors": errors}
        
        # 4. Validar que el campo existe
        try:
            field_check = await self.data_layer.get(f"/api/fields/{request.field_id}")
            if not field_check.get("ok"):
                errors.append("Campo no encontrado")
                return {"ok": False, "errors": errors}
        except Exception as e:
            errors.append(f"Error al verificar campo: {str(e)}")
            return {"ok": False, "errors": errors}
        
        # 5. Validar participantes existen
        if request.participants:
            valid, msg = await self._validate_participants(request.participants)
            if not valid:
                errors.append(msg)
                return {"ok": False, "errors": errors}
        
        # 6. Validar restricciones de rol + activity_type
        user_data = await self.data_layer.get(f"/api/users/{request.applicant_id}")
        if not user_data.get("ok"):
            errors.append("Usuario solicitante no encontrado")
            return {"ok": False, "errors": errors}
        
        user = user_data["user"]
        role_id = user.get("role_id")
        
        # Validar que el rol puede crear ese tipo de actividad
        activity_valid, activity_msg = self.rules.validate_activity_for_role(
            request.activity_type,
            role_id,
            request.participants
        )
        if not activity_valid:
            errors.append(activity_msg)
            return {"ok": False, "errors": errors}
        
        # 7. Validar que field_manager NO reserve
        if role_id == 3:
            errors.append("Los administradores de cancha no pueden crear reservas")
            return {"ok": False, "errors": errors}
        
        # 8. Detectar conflictos de horario (SOLAPAMIENTO)
        conflicts = await self._check_conflicts(request)
        if conflicts:
            return {
                "ok": False,
                "errors": [
                    f"Ya existe una reserva en ese horario. Conflictos detectados: {len(conflicts)}"
                ],
                "conflicts": conflicts
            }
        
        # 9. Calcular prioridad
        priority = await self._calculate_priority(request)
        
        # 10. Determinar estado inicial
        status = self.rules.get_initial_status(request.activity_type)
        
        # 11. Crear en Data Layer
        reservation_data = {
            "field_id": request.field_id,
            "applicant_id": request.applicant_id,
            "activity_type": request.activity_type,
            "start_datetime": request.start_datetime.isoformat(),
            "end_datetime": request.end_datetime.isoformat(),
            "priority": priority,
            "notes": request.notes,
            "status": status
        }
        
        try:
            result = await self.data_layer.post("/api/reservations", reservation_data)
        except httpx.HTTPStatusError as e:
            if e.response.status_code == 409:
                error_detail = e.response.json()
                error_msg = error_detail.get("error", "Conflicto al crear reserva")
                
                if "already exists" in error_msg.lower():
                    error_msg = "Ya existe una reserva en ese horario"
                elif "applicant" in error_msg.lower():
                    error_msg = "El usuario solicitante no existe"
                
                return {"ok": False, "errors": [error_msg]}
            
            return {"ok": False, "errors": [f"Error al crear reserva: {str(e)}"]}
        except Exception as e:
            return {"ok": False, "errors": [f"Error inesperado: {str(e)}"]}
        
        if not result.get("ok"):
            return result
        
        reservation = result["reservation"]
        
        # 12. Agregar participantes
        if request.participants:
            for p in request.participants:
                try:
                    await self.data_layer.post(
                        f"/api/reservations/{reservation['id']}/participants",
                        p.model_dump()
                    )
                except Exception as e:
                    print(f"Warning: Error al agregar participante: {e}")
        
        return {
            "ok": True,
            "reservation": reservation,
            "validations": ValidationResult(
                ds_check="passed",
                dcc_check="passed",
                conflict_check="passed",
                priority=priority
            ).model_dump(),
            "errors": []
        }
    
    async def _check_conflicts(self, request: ReservationCreateRequest) -> List[Dict]:
        """Detecta conflictos de horario consultando la Data Layer"""
        try:
            payload = {
                "field_id": request.field_id,
                "start_datetime": request.start_datetime.isoformat(),
                "end_datetime": request.end_datetime.isoformat()
            }
            
            print(f"🔍 Verificando conflictos con payload: {payload}")
            
            result = await self.data_layer.post("/api/reservations/check-overlap", payload)
            
            print(f"📡 Respuesta de Data Layer: {result}")
            
            if not isinstance(result, dict):
                print(f"⚠️ Respuesta inesperada (no es dict): {type(result)}")
                return []
            
            if not result.get("ok"):
                print(f"❌ Data Layer retornó ok=False: {result.get('error')}")
                return []
            
            # ✅ CORRECCIÓN: Acceder a result["overlap"]["has_conflict"]
            overlap_data = result.get("overlap", {})
            has_conflict = overlap_data.get("has_conflict", False)
            
            print(f"🎯 has_conflict = {has_conflict}")
            
            if not has_conflict:
                print("✅ No hay solapamiento")
                return []
            
            # ✅ CORRECCIÓN: Obtener conflicts del objeto overlap
            conflicts = overlap_data.get("conflicts", [])
            print(f"⚠️ Conflictos encontrados: {len(conflicts)}")
            print(f"📋 Detalles: {conflicts}")
            
            normalized = []
            
            for o in conflicts:
                start = o.get("start_datetime") or o.get("start")
                end = o.get("end_datetime") or o.get("end")
                
                normalized.append({
                    "id": o.get("id"),
                    "field_id": o.get("field_id"),
                    "start_datetime": start,
                    "end_datetime": end,
                    "activity_type": o.get("activity_type"),
                    "priority": o.get("priority"),
                    "status": o.get("status")
                })
            
            return normalized
        
        except Exception as e:
            print(f"💥 Error al verificar conflictos: {e}")
            import traceback
            traceback.print_exc()
            return []
    
    async def _validate_participants(self, participants: List) -> tuple[bool, str]:
        """Valida que todos los participantes existan"""
        for p in participants:
            try:
                user = await self.data_layer.get(f"/api/users/{p.participant_id}")
                if not user.get("ok"):
                    return False, f"Participante {p.participant_id} no encontrado"
            except:
                return False, f"Participante {p.participant_id} no encontrado"
        
        return True, ""
    
    async def _calculate_priority(self, request: ReservationCreateRequest) -> int:
        """Calcula prioridad de la reserva"""
        try:
            user_data = await self.data_layer.get(f"/api/users/{request.applicant_id}")
            
            if not user_data.get("ok"):
                return 4
            
            user = user_data["user"]
            role_name = user.get("role_name", "athlete")
            
            return self.rules.calculate_priority(request.activity_type, role_name)
        except:
            return 4