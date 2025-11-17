from fastapi import APIRouter, HTTPException
from app.services.reservation_orchestrator import ReservationOrchestrator
from app.models.schemas import (
    ReservationCreateRequest, 
    ReservationCreateResponse
)

router = APIRouter(prefix="/api/reservations", tags=["reservations"])

@router.post("/create-validated", response_model=ReservationCreateResponse)
async def create_reservation_validated(request: ReservationCreateRequest):
    """
    Crea una reserva aplicando todas las validaciones de negocio (DS/DCC)
    """
    orchestrator = ReservationOrchestrator()
    result = await orchestrator.create_with_validation(request)
    
    if not result.get("ok"):
        raise HTTPException(status_code=400, detail=result)
    
    return result