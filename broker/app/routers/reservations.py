from fastapi import APIRouter, HTTPException
from app.services.reservation_orchestrator import ReservationOrchestrator
from app.models.schemas import ReservationCreateRequest, ReservationCreateResponse

router = APIRouter(prefix="/api/reservations", tags=["reservations"])

@router.post("/create-validated", response_model=ReservationCreateResponse)
async def create_reservation_with_validation(request: ReservationCreateRequest):
    """
    Crea una reserva aplicando validaciones DS/DCC
    
    Validaciones aplicadas:
    - DS: Duración, anticipación, horario del campo
    - DCC: Fechas especiales bloqueadas
    - Conflictos de horario
    - Cálculo de prioridad
    """
    orchestrator = ReservationOrchestrator()
    result = await orchestrator.create_with_validation(request)
    
    if not result.get("ok"):
        raise HTTPException(status_code=400, detail=result)
    
    return result

@router.patch("/{reservation_id}/approve")
async def approve_reservation(reservation_id: int, approver_id: int):
    """Aprobar una reserva pendiente"""
    pass

@router.patch("/{reservation_id}/reject")
async def reject_reservation(reservation_id: int, approver_id: int, reason: str):
    """Rechazar una reserva"""
    pass