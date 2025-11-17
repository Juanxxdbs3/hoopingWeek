from fastapi import APIRouter, HTTPException, status, Body, BackgroundTasks
from datetime import datetime
from app.services.approval_orchestrator import ApprovalOrchestrator
from app.models.schemas import ApproveRequest, RejectRequest, SimpleResponse

router = APIRouter(prefix="/api/reservations", tags=["approvals"])

async def _log_notification(action: str, reservation_id: int, user_id: int, message: str):
    """Log de notificaciones (placeholder para sistema real)"""
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    print(f"""
    ═══════════════════════════════════════════════
    📬 NOTIFICACIÓN
    ═══════════════════════════════════════════════
    Timestamp: {timestamp}
    Action:    {action}
    User ID:   {user_id}
    Reserva:   {reservation_id}
    Message:   {message}
    ═══════════════════════════════════════════════
    """)

@router.patch("/{reservation_id}/approve", response_model=SimpleResponse)
async def approve_reservation(
    reservation_id: int, 
    body: ApproveRequest = Body(...),
    background_tasks: BackgroundTasks = BackgroundTasks()
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
    
    # Notificación en background
    reservation = result.get("reservation", {})
    applicant_id = reservation.get("applicant_id")
    if applicant_id:
        background_tasks.add_task(
            _log_notification,
            "APPROVAL",
            reservation_id,
            applicant_id,
            f"Tu reserva #{reservation_id} ha sido aprobada"
        )
    
    return result

@router.patch("/{reservation_id}/reject", response_model=SimpleResponse)
async def reject_reservation(
    reservation_id: int, 
    body: RejectRequest = Body(...),
    background_tasks: BackgroundTasks = BackgroundTasks()
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
    
    # Notificación en background
    reservation = result.get("reservation", {})
    applicant_id = reservation.get("applicant_id")
    if applicant_id:
        background_tasks.add_task(
            _log_notification,
            "REJECTION",
            reservation_id,
            applicant_id,
            f"Tu reserva #{reservation_id} ha sido rechazada"
        )
    
    return result

@router.patch("/{reservation_id}/cancel", response_model=SimpleResponse)
async def cancel_reservation(
    reservation_id: int, 
    body: dict = Body(...),
    background_tasks: BackgroundTasks = BackgroundTasks()
):
    """
    Cancela una reserva
    
    **Body esperado:**
    ```json
    {
      "cancelled_by": 10,
      "cancellation_reason": "No puedo asistir"
    }
    ```
    
    **Permisos:**
    - Super admin: cualquier reserva
    - Applicant: su propia reserva
    - Field manager: reservas de su campo
    """
    cancelled_by = body.get("cancelled_by")
    cancellation_reason = body.get("cancellation_reason")
    
    if not cancelled_by or not cancellation_reason:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="cancelled_by y cancellation_reason son requeridos"
        )
    
    if len(cancellation_reason) < 5:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="cancellation_reason debe tener al menos 5 caracteres"
        )
    
    print(f"🔍 Cancelando reserva {reservation_id} por usuario {cancelled_by}")
    
    orchestrator = ApprovalOrchestrator()
    result = await orchestrator.cancel_reservation(
        reservation_id,
        cancelled_by,
        cancellation_reason
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
    
    # Notificación en background
    reservation = result.get("reservation", {})
    applicant_id = reservation.get("applicant_id")
    if applicant_id and applicant_id != cancelled_by:
        background_tasks.add_task(
            _log_notification,
            "CANCELLATION",
            reservation_id,
            applicant_id,
            f"Reserva #{reservation_id} cancelada: {cancellation_reason}"
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