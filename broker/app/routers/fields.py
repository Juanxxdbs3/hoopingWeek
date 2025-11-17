# broker/app/routers/fields.py (nuevo archivo)
from fastapi import APIRouter, HTTPException, Query, status
from datetime import date
from app.services.availability_orchestrator import AvailabilityOrchestrator

router = APIRouter(prefix="/api/fields", tags=["fields"])

@router.get("/{field_id}/availability")
async def get_field_availability(
    field_id: int,
    date: date = Query(..., description="Fecha en formato YYYY-MM-DD")
):
    """
    Obtiene disponibilidad de un campo en una fecha específica
    
    **Retorna:**
    - Slots de 30 minutos con estado (disponible/ocupado)
    - Horarios de apertura/cierre
    - Excepciones del día (mantenimientos, horarios especiales)
    - Reservas existentes
    
    **Ejemplo:**
    ```
    GET /api/fields/5/availability?date=2025-12-10
    ```
    
    **Reglas:**
    - Solo fechas futuras
    - Anticipación mínima: 1 hora
    - Si hay excepción que cierra el campo → slots vacíos
    - Si hay horario reducido → ajusta slots al horario especial
    """
    print(f"🔍 Consultando disponibilidad: field_id={field_id}, date={date}")
    
    orchestrator = AvailabilityOrchestrator()
    result = await orchestrator.get_field_availability(field_id, date)
    
    print(f"📊 Resultado: ok={result.get('ok')}, slots={len(result.get('available_slots', []))}")
    
    if not result.get("ok"):
        message = result.get("message", "Error al obtener disponibilidad")
        
        # Determinar código de error apropiado
        if "no encontrado" in message.lower() or "not found" in message.lower():
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail=message
            )
        elif "pasada" in message.lower():
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail=message
            )
        else:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail=message
            )
    
    return result