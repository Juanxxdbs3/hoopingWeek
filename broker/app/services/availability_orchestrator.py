# broker/app/services/availability_orchestrator.py
from datetime import datetime, date, time, timedelta
from typing import List, Dict, Any, Optional
from app.services.data_layer_client import DataLayerClient

class AvailabilityOrchestrator:
    """Calcula disponibilidad de campos"""
    
    def __init__(self):
        self.data_layer = DataLayerClient()
    
    async def get_field_availability(self, field_id: int, target_date: date) -> Dict[str, Any]:
        """
        Obtiene disponibilidad de un campo en una fecha específica
        """
        
        # 1. Validar fecha
        now = datetime.now()
        target_datetime = datetime.combine(target_date, time.min)
        
        if target_datetime.date() < now.date():
            return {
                "ok": False,
                "message": "No se puede consultar disponibilidad de fechas pasadas"
            }
        
        # 2. Obtener datos del campo
        try:
            field_response = await self.data_layer.get(f"/api/fields/{field_id}")
            if not field_response.get("ok"):
                return {"ok": False, "message": "Campo no encontrado"}
            
            field = field_response.get("field")
        except Exception as e:
            # Si el campo no existe, Data Layer devuelve 404
            if "404" in str(e):
                return {"ok": False, "message": "Campo no encontrado"}
            print(f"❌ Error al obtener campo: {e}")
            return {"ok": False, "message": "Error al obtener información del campo"}
        
        # 3. Obtener horarios regulares
        try:
            hours_response = await self.data_layer.get(f"/api/fields/{field_id}/operating-hours")
            if not hours_response.get("ok"):
                return {"ok": False, "message": "No se encontraron horarios para el campo"}
            
            operating_hours = hours_response.get("operating_hours", [])
        except Exception as e:
            print(f"❌ Error al obtener horarios: {e}")
            return {"ok": False, "message": "Error al obtener horarios del campo"}
        
        # 4. Obtener excepciones para la fecha (puede no existir)
        date_str = target_date.strftime("%Y-%m-%d")
        exception = None
        
        try:
            exception_response = await self.data_layer.get(
                f"/api/fields/{field_id}/exceptions",
                {"date": date_str}
            )
            
            if exception_response.get("ok"):
                exception = exception_response.get("exception")
        except Exception as e:
            # 404 es normal cuando no hay excepción para esa fecha
            if "404" not in str(e):
                print(f"⚠️ Error inesperado al obtener excepciones: {e}")
        
        # 5. Obtener reservas del día
        try:
            slots_response = await self.data_layer.get(
                f"/api/fields/{field_id}/reserved-slots",
                {"date": date_str}
            )
            
            reserved_slots = []
            if slots_response.get("ok"):
                reserved_slots = slots_response.get("reserved_slots", [])
        except Exception as e:
            print(f"⚠️ Error al obtener slots reservados: {e}")
            reserved_slots = []
        
        # 6. Calcular disponibilidad
        available_slots = self._calculate_available_slots(
            target_date,
            operating_hours,
            exception,
            reserved_slots,
            now
        )
        
        return {
            "ok": True,
            "field": {
                "id": field.get("id"),
                "name": field.get("name")
            },
            "date": date_str,
            "day_of_week": target_date.isoweekday() % 7,  # 0=Sunday, 6=Saturday
            "available_slots": available_slots,
            "reserved_count": len(reserved_slots),
            "exception": exception
        }
    
    def _calculate_available_slots(
        self,
        target_date: date,
        operating_hours: List[Dict],
        exception: Optional[Dict],
        reserved_slots: List[Dict],
        now: datetime
    ) -> List[Dict]:
        """
        Genera slots de 30 minutos marcando cuáles están disponibles
        """
        day_of_week = target_date.isoweekday() % 7
        
        # Determinar horario del día (regular o excepción)
        if exception and exception.get("overrides_regular"):
            open_time_str = exception.get("open_time")
            close_time_str = exception.get("close_time")
            
            # Si no hay horarios especiales Y overrides_regular=1, el campo está CERRADO
            if not open_time_str or not close_time_str:
                print(f"🔒 Campo cerrado por excepción: {exception.get('reason')}")
                return []
            
            print(f"⏰ Usando horario especial: {open_time_str} - {close_time_str} (Razón: {exception.get('reason')})")
        else:
            # Buscar horario regular para el día de la semana
            day_schedule = next(
                (h for h in operating_hours if h.get("day_of_week") == day_of_week),
                None
            )
            
            if not day_schedule:
                print(f"❌ No hay horario regular para day_of_week={day_of_week}")
                return []
            
            open_time_str = day_schedule.get("start_time")
            close_time_str = day_schedule.get("end_time")
            print(f"⏰ Usando horario regular: {open_time_str} - {close_time_str}")
        
        # Parsear horarios
        try:
            open_time = datetime.strptime(open_time_str, "%H:%M:%S").time()
            close_time = datetime.strptime(close_time_str, "%H:%M:%S").time()
        except Exception as e:
            print(f"❌ Error parseando horarios: open={open_time_str}, close={close_time_str} | {e}")
            return []
        
        # Generar slots de 30 minutos
        slots = []
        current_time = datetime.combine(target_date, open_time)
        end_time = datetime.combine(target_date, close_time)
        
        while current_time < end_time:
            slot_end = current_time + timedelta(minutes=30)
            
            # Validar anticipación mínima (1 hora)
            can_reserve = (current_time - now).total_seconds() >= 3600
            
            # Verificar si está reservado
            slot_start_str = current_time.strftime("%H:%M:%S")
            slot_end_str = slot_end.strftime("%H:%M:%S")
            
            is_reserved = any(
                self._times_overlap(
                    slot_start_str, slot_end_str,
                    r.get("start_time"), r.get("end_time")
                )
                for r in reserved_slots
            )
            
            reason = self._get_unavailability_reason(
                is_reserved, can_reserve, reserved_slots, slot_start_str, slot_end_str
            )
            
            slots.append({
                "start": slot_start_str,
                "end": slot_end_str,
                "available": not is_reserved and can_reserve,
                "reason": reason
            })
            
            current_time = slot_end
        
        return slots
    
    def _times_overlap(self, start1: str, end1: str, start2: str, end2: str) -> bool:
        """Verifica si dos rangos de tiempo se solapan"""
        return start1 < end2 and end1 > start2
    
    def _get_unavailability_reason(
        self,
        is_reserved: bool,
        can_reserve: bool,
        reserved_slots: List[Dict],
        slot_start: str,
        slot_end: str
    ) -> Optional[str]:
        """Retorna el motivo por el que un slot no está disponible"""
        if is_reserved:
            reservation = next(
                (r for r in reserved_slots if self._times_overlap(
                    slot_start, slot_end,
                    r.get("start_time"), r.get("end_time")
                )),
                None
            )
            if reservation:
                return f"Reserva existente (ID: {reservation.get('id')}, estado: {reservation.get('status')})"
        
        if not can_reserve:
            return "Anticipación mínima no cumplida (1 hora)"
        
        return None