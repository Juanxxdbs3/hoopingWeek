# broker/app/routers/reservation_approvals.py
from fastapi import APIRouter, HTTPException, status, Body, BackgroundTasks, Depends
from datetime import datetime
from typing import Dict, Any
from app.services.approval_orchestrator import ApprovalOrchestrator
from app.models.schemas import ApproveRequest, RejectRequest
from app.core.deps import get_current_active_user, require_any_role
from app.config.settings import settings

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

@router.patch("/{reservation_id}/approve")
async def approve_reservation(
    reservation_id: int, 
    body: ApproveRequest = Body(...),
    background_tasks: BackgroundTasks = BackgroundTasks(),
    current_user: Dict[str, Any] = Depends(
        require_any_role(
            settings.ROLE_FIELD_MANAGER_ID,
            settings.ROLE_SUPER_ADMIN_ID
        )
    )
):
    """
    Aprueba una reserva pendiente.
    
    **Permisos:**
    - Field Manager: solo puede aprobar reservas de campos que gestiona en su turno
    - Super Admin: puede aprobar cualquier reserva
    
    **Validaciones:**
    - El approver_id debe coincidir con el usuario autenticado
    - La reserva debe estar en estado 'pending'
    - Si es Field Manager, debe estar en su turno asignado
    """
    user_id = current_user.get("id")
    role_id = current_user.get("role_id")

    # Validar que approver_id coincida con usuario autenticado
    if body.approver_id != user_id:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="El approver_id debe coincidir con tu usuario"
        )

    print(f"🔍 Aprobando reserva {reservation_id} por usuario {body.approver_id} (role: {role_id})")

    orchestrator = ApprovalOrchestrator()
    result = await orchestrator.approve_reservation(
        reservation_id, 
        body.approver_id, 
        body.note
    )

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


@router.patch("/{reservation_id}/reject")
async def reject_reservation(
    reservation_id: int, 
    body: RejectRequest = Body(...),
    background_tasks: BackgroundTasks = BackgroundTasks(),
    current_user: Dict[str, Any] = Depends(
        require_any_role(
            settings.ROLE_FIELD_MANAGER_ID,
            settings.ROLE_SUPER_ADMIN_ID
        )
    )
):
    """
    Rechaza una reserva pendiente.
    
    **Mismas validaciones que approve**
    """
    user_id = current_user.get("id")
    role_id = current_user.get("role_id")

    if body.approver_id != user_id:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="El approver_id debe coincidir con tu usuario"
        )

    orchestrator = ApprovalOrchestrator()
    result = await orchestrator.reject_reservation(
        reservation_id,
        body.approver_id,
        body.rejection_reason
    )

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

    # Notificación
    reservation = result.get("reservation", {})
    applicant_id = reservation.get("applicant_id")
    if applicant_id:
        background_tasks.add_task(
            _log_notification,
            "REJECTION",
            reservation_id,
            applicant_id,
            f"Tu reserva #{reservation_id} ha sido rechazada: {body.rejection_reason}"
        )

    return result


@router.patch("/{reservation_id}/cancel")
async def cancel_reservation(
    reservation_id: int, 
    body: dict = Body(...),
    background_tasks: BackgroundTasks = BackgroundTasks(),
    current_user: Dict[str, Any] = Depends(get_current_active_user)
):
    """
    Cancela una reserva.
    
    **Permisos:**
    - Atleta/Entrenador: solo puede cancelar sus propias reservas
    - Field Manager: puede cancelar reservas de sus campos
    - Super Admin: puede cancelar cualquiera
    """
    cancelled_by = body.get("cancelled_by")
    cancellation_reason = body.get("cancellation_reason")

    if not cancelled_by or not cancellation_reason:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="cancelled_by y cancellation_reason son requeridos"
        )

    user_id = current_user.get("id")
    role_id = current_user.get("role_id")

    # Validar que cancelled_by coincida con usuario autenticado (salvo SuperAdmin)
    if role_id != settings.ROLE_SUPER_ADMIN_ID and cancelled_by != user_id:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Solo puedes cancelar tus propias reservas"
        )

    orchestrator = ApprovalOrchestrator()
    result = await orchestrator.cancel_reservation(
        reservation_id,
        cancelled_by,
        cancellation_reason
    )

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

    # Notificación
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

'''
@router.get("", response_model=dict)
async def list_reservations(
    field_id: int = None,
    status: str = None,
    limit: int = 100,
    offset: int = 0
):
    """
    Lista reservas con filtros opcionales
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
'''