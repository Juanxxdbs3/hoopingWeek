from fastapi import APIRouter, HTTPException, Depends, Query
from typing import Dict, Any, Optional
from app.services.reservation_orchestrator import ReservationOrchestrator
from app.models.schemas import (
    ReservationCreateRequest, 
    ReservationCreateResponse
)
from app.core.deps import get_current_active_user, require_any_role
from app.config.settings import settings

router = APIRouter(prefix="/api/reservations", tags=["reservations"])

@router.post("/create-validated", response_model=ReservationCreateResponse)
async def create_reservation_validated(
    request: ReservationCreateRequest,
    current_user: Dict[str, Any] = Depends(get_current_active_user)
):
    """
    Crea una reserva aplicando todas las validaciones de negocio.
    
    **Permisos:**
    - Atletas: pueden crear reservas individuales/equipo
    - Entrenadores: pueden crear cualquier tipo de reserva para sus equipos
    - Field Managers: pueden crear reservas (uso administrativo)
    - Super Admin: acceso completo
    
    **Validación adicional:**
    - El applicant_id debe coincidir con el usuario autenticado (salvo SuperAdmin)
    """
    user_id = current_user.get("id")
    role_id = current_user.get("role_id")
    
    # Validación: el applicant_id debe ser el usuario actual (salvo SuperAdmin)
    if role_id != settings.ROLE_SUPER_ADMIN_ID:
        if request.applicant_id != user_id:
            raise HTTPException(
                status_code=403,
                detail="No puedes crear reservas en nombre de otro usuario"
            )
    
    orchestrator = ReservationOrchestrator()
    result = await orchestrator.create_with_validation(request)
    
    if not result.get("ok"):
        raise HTTPException(status_code=400, detail=result)
    
    return result


@router.get("")
async def list_reservations(
    field_id: Optional[int] = None,
    applicant_id: Optional[int] = None,
    status: Optional[str] = None,
    limit: int = Query(20, le=100),
    offset: int = Query(0, ge=0),
    current_user: Dict[str, Any] = Depends(get_current_active_user)  # ⬅️ AÑADIDO AQUÍ
):
    """
    Lista reservas con filtros opcionales.
    
    **Permisos:**
    - Atletas: solo ven sus propias reservas
    - Entrenadores: ven reservas de sus equipos
    - Field Managers: ven reservas de sus campos
    - Super Admin: ve todas
    """
    from app.services.data_layer_client import DataLayerClient

    client = DataLayerClient()
    params = {"limit": limit, "offset": offset}

    role_id = current_user.get("role_id")
    user_id = current_user.get("id")

    # Si no es SuperAdmin, forzar filtro por applicant_id del usuario actual
    if role_id != settings.ROLE_SUPER_ADMIN_ID:
        params["applicant_id"] = user_id
    else:
        # Si es SuperAdmin y pasó applicant_id como query param, usarlo
        if applicant_id is not None:
            params["applicant_id"] = applicant_id

    if field_id:
        params["field_id"] = field_id
    if status:
        params["status"] = status

    result = await client.get("/api/reservations", params)
    return result