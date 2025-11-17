from typing import Optional
from datetime import datetime
from app.services.data_layer_client import DataLayerClient
from app.config.settings import settings

class ApprovalOrchestrator:
    """Orquestador de aprobaciones y rechazos de reservas"""
    
    def __init__(self):
        self.data_layer = DataLayerClient()
        self.SUPER_ADMIN_ROLE = settings.ROLE_SUPER_ADMIN_ID
        self.FIELD_MANAGER_ROLE = settings.ROLE_FIELD_MANAGER_ID
    
    async def _is_field_manager_for_reservation(self, manager_id: int, reservation: dict) -> bool:
        """Verifica si manager_id cubre el campo en la fecha/hora de la reserva"""
        field_id = reservation.get("field_id")
        start_dt = reservation.get("start_datetime")
        
        if not (field_id and start_dt):
            return False
        
        try:
            # Parsear fecha y hora
            if "T" in start_dt:
                dt = datetime.fromisoformat(start_dt)
            else:
                dt = datetime.strptime(start_dt, "%Y-%m-%d %H:%M:%S")
            
            day_of_week = dt.isoweekday()  # 1=Monday, 7=Sunday
            time_str = dt.strftime("%H:%M:%S")
            
            # Consultar turnos del manager para ese campo y día
            resp = await self.data_layer.get(
                "/api/manager-shifts",
                {
                    "manager_id": manager_id,
                    "field_id": field_id,
                    "day_of_week": day_of_week
                }
            )
            
            if not resp.get("ok"):
                return False
            
            shifts = resp.get("manager_shifts", {}).get("data", [])
            
            # Verificar si algún turno cubre la hora de inicio
            for shift in shifts:
                if not shift.get("active"):
                    continue
                
                start_time = shift.get("start_time", "00:00:00")
                end_time = shift.get("end_time", "23:59:59")
                
                if start_time <= time_str <= end_time:
                    return True
            
            return False
        
        except Exception as e:
            print(f"Error verificando manager shifts: {e}")
            return False
    
    async def approve_reservation(
        self, 
        reservation_id: int, 
        approver_id: int, 
        note: Optional[str] = None
    ) -> dict:
        """
        Aprueba una reserva
        
        Permisos:
        - Super admin: puede aprobar cualquier reserva
        - Field manager: solo puede aprobar reservas de campos que administra
        """
        # 1. Cargar reserva
        res = await self.data_layer.get(f"/api/reservations/{reservation_id}")
        if not res.get("ok"):
            return {"ok": False, "message": "Reserva no encontrada"}
        
        reservation = res.get("reservation")
        
        # 2. Verificar que está en estado pending
        if reservation.get("status") != "pending":
            return {
                "ok": False, 
                "message": f"Solo se pueden aprobar reservas pendientes. Estado actual: {reservation.get('status')}"
            }
        
        # 3. Cargar usuario que aprueba
        u = await self.data_layer.get(f"/api/users/{approver_id}")
        if not u.get("ok"):
            return {"ok": False, "message": "Usuario aprobador no encontrado"}
        
        user = u["user"]
        role_id = user.get("role_id")
        
        # 4. Validar permisos
        if role_id == self.SUPER_ADMIN_ROLE:
            allowed = True
        elif role_id == self.FIELD_MANAGER_ROLE:
            allowed = await self._is_field_manager_for_reservation(approver_id, reservation)
        else:
            allowed = False
        
        if not allowed:
            return {
                "ok": False, 
                "message": "No tiene permiso para aprobar esta reserva", 
                "code": 403
            }
        
        # 5. Actualizar estado en Data Layer
        payload = {
            "status": "approved",
            "approved_by": approver_id
        }
        
        if note:
            # Si hay nota, agregarla (verificar si Data Layer soporta 'notes' en status update)
            payload["notes"] = note
        
        try:
            result = await self.data_layer.patch(
                f"/api/reservations/{reservation_id}/status",
                payload
            )
            
            if not result.get("ok"):
                return {"ok": False, "message": result.get("message", "Error al aprobar")}
            
            # 6. Obtener reserva actualizada
            updated = await self.data_layer.get(f"/api/reservations/{reservation_id}")
            
            return {
                "ok": True,
                "message": "Reserva aprobada exitosamente",
                "reservation": updated.get("reservation")
            }
        
        except Exception as e:
            return {"ok": False, "message": f"Error al comunicarse con Data Layer: {e}"}
    
    async def reject_reservation(
        self, 
        reservation_id: int, 
        approver_id: int, 
        rejection_reason: str
    ) -> dict:
        """
        Rechaza una reserva
        
        Permisos: Mismos que approve_reservation
        """
        # 1. Cargar reserva
        res = await self.data_layer.get(f"/api/reservations/{reservation_id}")
        if not res.get("ok"):
            return {"ok": False, "message": "Reserva no encontrada"}
        
        reservation = res.get("reservation")
        
        # 2. Verificar estado
        if reservation.get("status") != "pending":
            return {
                "ok": False,
                "message": f"Solo se pueden rechazar reservas pendientes. Estado actual: {reservation.get('status')}"
            }
        
        # 3. Cargar usuario que rechaza
        u = await self.data_layer.get(f"/api/users/{approver_id}")
        if not u.get("ok"):
            return {"ok": False, "message": "Usuario aprobador no encontrado"}
        
        user = u["user"]
        role_id = user.get("role_id")
        
        # 4. Validar permisos
        if role_id == self.SUPER_ADMIN_ROLE:
            allowed = True
        elif role_id == self.FIELD_MANAGER_ROLE:
            allowed = await self._is_field_manager_for_reservation(approver_id, reservation)
        else:
            allowed = False
        
        if not allowed:
            return {
                "ok": False,
                "message": "No tiene permiso para rechazar esta reserva",
                "code": 403
            }
        
        # 5. Actualizar estado
        payload = {
            "status": "rejected",
            "approved_by": approver_id,
            "rejection_reason": rejection_reason
        }
        
        try:
            result = await self.data_layer.patch(
                f"/api/reservations/{reservation_id}/status",
                payload
            )
            
            if not result.get("ok"):
                return {"ok": False, "message": result.get("message", "Error al rechazar")}
            
            # 6. Obtener reserva actualizada
            updated = await self.data_layer.get(f"/api/reservations/{reservation_id}")
            
            return {
                "ok": True,
                "message": f"Reserva rechazada: {rejection_reason}",
                "reservation": updated.get("reservation")
            }
        
        except Exception as e:
            return {"ok": False, "message": f"Error al comunicarse con Data Layer: {e}"}