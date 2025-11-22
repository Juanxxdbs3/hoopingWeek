# broker/app/routers/fields.py (nuevo archivo)
from fastapi import APIRouter, HTTPException, Query, status
from datetime import date
from typing import Optional
from app.services.availability_orchestrator import AvailabilityOrchestrator
from app.services.data_layer_client import DataLayerClient

router = APIRouter(prefix="/api/fields", tags=["fields"])


@router.get("")
async def list_fields(
    state: Optional[str] = None,
    location: Optional[str] = None,
    sport: Optional[str] = None,
    limit: int = Query(100, le=500),
    offset: int = Query(0, ge=0)
):
    """
    Lista todos los campos con filtros opcionales
    
    **Filtros:**
    - state: active/inactive
    - location: texto parcial de ubicación
    - sport: deporte permitido (basketball, volleyball, etc.)
    """
    client = DataLayerClient()
    params = {"limit": limit, "offset": offset}
    
    if state:
        params["state"] = state
    if location:
        params["location"] = location
    if sport:
        params["sport"] = sport
    
    try:
        result = await client.get("/api/fields", params)
        return result
    except Exception as e:
        print(f"❌ Error listando campos: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Error al obtener listado de campos"
        )


@router.get("/{field_id}")
async def get_field(field_id: int):
    """Obtener información detallada de un campo"""
    client = DataLayerClient()
    
    try:
        result = await client.get(f"/api/fields/{field_id}")
        return result
    except Exception as e:
        if "404" in str(e):
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail=f"Campo {field_id} no encontrado"
            )
        print(f"❌ Error obteniendo campo {field_id}: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Error al obtener campo"
        )


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
    """
    print(f"🔍 Consultando disponibilidad: field_id={field_id}, date={date}")
    
    orchestrator = AvailabilityOrchestrator()
    result = await orchestrator.get_field_availability(field_id, date)
    
    print(f"📊 Resultado: ok={result.get('ok')}, slots={len(result.get('available_slots', []))}")
    
    if not result.get("ok"):
        message = result.get("message", "Error al obtener disponibilidad")
        
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