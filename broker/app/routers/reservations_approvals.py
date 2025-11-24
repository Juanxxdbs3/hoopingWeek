# broker/app/routers/reservation_approvals.py
from fastapi import APIRouter, HTTPException, status, Body, BackgroundTasks, Depends
import httpx
from typing import Dict, Any
from app.services.approval_orchestrator import ApprovalOrchestrator
from app.models.schemas import ApproveRequest, RejectRequest, ReservationCancelRequest
from app.core.deps import get_current_active_user, require_any_role
from app.config.settings import settings

router = APIRouter(prefix="/api/reservations", tags=["approvals"])

async def _log_notification(action: str, reservation_id: int, user_id: int, message: str):
    """Log de notificaciones (placeholder)"""
    print(f"🔔 NOTIFICACIÓN [{action}] User {user_id} - Reserva {reservation_id}: {message}")

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
    Aprobar reserva.
    - Si es Manager, valida turno.
    - Si es Campeonato, desplaza reservas conflictivas.
    """
    try:
        orchestrator = ApprovalOrchestrator()
        result = await orchestrator.approve_reservation(
            reservation_id=reservation_id, 
            approver_id=current_user["id"], 
            note=body.note
        )
    except httpx.HTTPStatusError as e:
        status_code = e.response.status_code
        try:
            detail = e.response.json()
        except Exception:
            detail = str(e)
        raise HTTPException(status_code=status_code, detail=detail)
    except Exception as e:
        print("❌ Error approving reservation:", e)
        raise HTTPException(status_code=500, detail="Error al aprobar reserva")

    if not result.get("ok"):
        code = result.get("code", 400)
        raise HTTPException(status_code=code, detail=result.get("message"))

    # Notificación básica
    reservation = result.get("reservation", {})
    if reservation.get("applicant_id"):
        background_tasks.add_task(
            _log_notification, "APPROVED", reservation_id, reservation.get("applicant_id"), "Tu reserva ha sido aprobada."
        )
    
    # Notificar desplazamientos (si hubo)
    displaced = result.get("displaced_reservations", [])
    if displaced:
        print(f"⚠️ SE DESPLAZARON {len(displaced)} RESERVAS AUTOMÁTICAMENTE: {displaced}")

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
    try:
        orchestrator = ApprovalOrchestrator()
        result = await orchestrator.reject_reservation(
            reservation_id=reservation_id,
            approver_id=current_user["id"],
            reason=body.rejection_reason
        )
    except httpx.HTTPStatusError as e:
        status_code = e.response.status_code
        try:
            detail = e.response.json()
        except Exception:
            detail = str(e)
        # Si Data Layer retorna 422 por motivo muy corto, lo propagamos con detalle legible
        raise HTTPException(status_code=status_code, detail=detail)
    except Exception as e:
        print("❌ Error rejecting reservation:", e)
        raise HTTPException(status_code=500, detail="Error al rechazar reserva")

    if not result.get("ok"):
        raise HTTPException(status_code=500, detail=result.get("message"))

    return result


@router.patch("/{reservation_id}/cancel")
async def cancel_reservation(
    reservation_id: int, 
    body: ReservationCancelRequest = Body(...),
    background_tasks: BackgroundTasks = BackgroundTasks(),
    current_user: Dict[str, Any] = Depends(get_current_active_user)
):
    """
    Cancelar reserva.
    """
    orchestrator = ApprovalOrchestrator()
    
    # TODO: Validar aquí si el usuario es dueño de la reserva si no es admin
    # Por ahora confiamos en la lógica básica o agregamos validación rápida:
    # if role != admin and user_id != reservation.applicant_id: raise 403
    
    result = await orchestrator.cancel_reservation(
        reservation_id=reservation_id,
        user_id=current_user["id"],
        reason=body.reason
    )

    if not result.get("ok"):
        raise HTTPException(status_code=500, detail=result.get("message"))

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