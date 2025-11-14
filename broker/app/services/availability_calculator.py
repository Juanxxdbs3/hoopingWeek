# app/services/availability_calculator.py
from datetime import datetime, timedelta
from typing import List, Dict

class AvailabilityCalculator:
    def __init__(self, data_layer_client):
        self.data_layer = data_layer_client
    
    async def calculate_availability(self, field_id: int, date: str) -> Dict:
        """
        Calcula disponibilidad de un campo en una fecha
        
        Retorna:
        {
            "date": "2025-11-20",
            "field_id": 3,
            "operating_hours": {"open": "06:00", "close": "22:00"},
            "reserved_slots": [...],
            "available_slots": [...]
        }
        """
        # 1. Obtener horario de operación
        hours = await self._get_operating_hours(field_id, date)
        
        # 2. Obtener reservas existentes
        reservations = await self._get_reservations(field_id, date)
        
        # 3. Calcular slots disponibles
        available = self._calculate_slots(hours, reservations)
        
        return {
            "date": date,
            "field_id": field_id,
            "operating_hours": hours,
            "reserved_slots": reservations,
            "available_slots": available
        }
    
    async def _get_operating_hours(self, field_id: int, date: str) -> Dict:
        """Obtiene horario de operación (regular + excepciones)"""
        result = await self.data_layer.get(
            f"/api/fields/{field_id}/operating-hours",
            {"date": date}
        )
        return result.get("hours", {"open": "06:00", "close": "22:00"})
    
    async def _get_reservations(self, field_id: int, date: str) -> List[Dict]:
        """Obtiene reservas del día"""
        result = await self.data_layer.get(
            f"/api/reservations",
            {"field_id": field_id, "date": date}
        )
        return result.get("reservations", {}).get("data", [])
    
    def _calculate_slots(self, hours: Dict, reservations: List[Dict]) -> List[Dict]:
        """Calcula slots libres de 30 min"""
        # Implementación del algoritmo de slots
        # (código complejo, lo implementamos después si quieres)
        return []