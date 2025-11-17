from fastapi import APIRouter, HTTPException, status, Body
from app.services.approval_orchestrator import ApprovalOrchestrator
from app.models.schemas import ApproveRequest, RejectRequest, SimpleResponse

router = APIRouter(prefix="/api/reservations", tags=["approvals"])

@router.patch("/{reservation_id}/approve", response_model=SimpleResponse)
async def approve_reservation(
    reservation_id: int, 
    body: ApproveRequest = Body(...)
):
    """
    Aprueba una reserva pendiente
    
    **Body esperado:**
    ```json
    {
      "approver_id": 1,
      "note": "Opcional"
    }
    ```
    
    **Permisos:**
    - Super admin: puede aprobar cualquier reserva
    - Field manager: solo puede aprobar reservas de campos que administra en su turno
    """
    print(f"🔍 Aprobando reserva {reservation_id} por usuario {body.approver_id}")
    
    orchestrator = ApprovalOrchestrator()
    result = await orchestrator.approve_reservation(
        reservation_id, 
        body.approver_id, 
        body.note
    )
    
    print(f"📊 Resultado: {result}")
    
    if not result.get("ok"):
        code = result.get("code", 400)
        if code == 403:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail=result.get("message")
            )
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=result.get("message")
        )
    
    return result

@router.patch("/{reservation_id}/reject", response_model=SimpleResponse)
async def reject_reservation(
    reservation_id: int, 
    body: RejectRequest = Body(...)
):
    """
    Rechaza una reserva pendiente
    
    **Body esperado:**
    ```json
    {
      "approver_id": 1,
      "rejection_reason": "Motivo del rechazo (mínimo 10 caracteres)"
    }
    ```
    
    **Permisos:** Mismos que approve
    """
    print(f"🔍 Rechazando reserva {reservation_id} por usuario {body.approver_id}")
    
    orchestrator = ApprovalOrchestrator()
    result = await orchestrator.reject_reservation(
        reservation_id,
        body.approver_id,
        body.rejection_reason
    )
    
    print(f"📊 Resultado: {result}")
    
    if not result.get("ok"):
        code = result.get("code", 400)
        if code == 403:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail=result.get("message")
            )
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=result.get("message")
        )
    
    return result

@router.get("", response_model=dict)
async def list_reservations(
    field_id: int = None,
    status: str = None,
    limit: int = 100,
    offset: int = 0
):
    """
    Lista reservas con filtros opcionales
    
    **Query params:**
    - field_id: Filtrar por campo (opcional)
    - status: Filtrar por estado (pending, approved, rejected, cancelled)
    - limit: Número máximo de resultados (default: 100)
    - offset: Desplazamiento para paginación (default: 0)
    
    **Ejemplo:**
    ```
    GET /api/reservations?field_id=3&status=pending
    ```
    """
    from app.services.data_layer_client import DataLayerClient
    
    client = DataLayerClient()
    params = {"limit": limit, "offset": offset}
    
    if field_id:
        params["field_id"] = field_id
    if status:
        params["status"] = status
    
    result = await client.get("/api/reservations", params)
    return result