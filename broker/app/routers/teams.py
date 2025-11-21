# broker/app/routers/teams.py
from fastapi import APIRouter, HTTPException, status, Depends
from typing import Dict, Any
from app.services.data_layer_client import DataLayerClient
from app.models.schemas import TeamCreate, TeamUpdate, TeamResponse, TeamMemberAdd
from app.core.deps import get_current_active_user, require_any_role
from app.config.settings import settings

router = APIRouter(prefix="/api/teams", tags=["teams"])


@router.post("", status_code=status.HTTP_201_CREATED)
async def create_team(
    payload: TeamCreate,
    user: Dict[str, Any] = Depends(require_any_role(settings.ROLE_TRAINER_ID, settings.ROLE_SUPER_ADMIN_ID))
):
    """Crear equipo. Trainer se asigna automáticamente como trainer_id si no viene en payload."""
    client = DataLayerClient()
    body = payload.model_dump()

    # Si es trainer, forzar su ID como trainer_id
    if user.get("role_id") == settings.ROLE_TRAINER_ID:
        body["trainer_id"] = user.get("id")

    try:
        resp = await client.post("/api/teams", body)
        return resp
    except Exception as e:
        print("❌ Error creating team:", e)
        raise HTTPException(status_code=500, detail="Error creando equipo")


@router.get("")
async def list_teams(trainer_id: int = None, sport: str = None, limit: int = 100, offset: int = 0):
    """Listar equipos con filtros opcionales"""
    client = DataLayerClient()
    params = {"limit": limit, "offset": offset}
    if trainer_id:
        params["trainer_id"] = trainer_id
    if sport:
        params["sport"] = sport
    try:
        return await client.get("/api/teams", params)
    except Exception as e:
        print("❌ Error listing teams:", e)
        raise HTTPException(status_code=500, detail="Error listando equipos")


@router.get("/{team_id}")
async def get_team(team_id: int):
    """Obtener un equipo por ID"""
    client = DataLayerClient()
    try:
        resp = await client.get(f"/api/teams/{team_id}")
        return resp
    except Exception as e:
        if "404" in str(e):
            raise HTTPException(status_code=404, detail="Equipo no encontrado")
        raise HTTPException(status_code=500, detail="Error obteniendo equipo")


@router.put("/{team_id}")
async def update_team(
    team_id: int,
    payload: TeamUpdate,
    user: Dict[str, Any] = Depends(get_current_active_user)
):
    """Actualizar equipo. Solo el trainer del equipo o SuperAdmin pueden modificar."""
    client = DataLayerClient()
    data = {k: v for k, v in payload.model_dump().items() if v is not None}
    
    try:
        # Verificar que existe y obtener trainer_id
        resp_existing = await client.get(f"/api/teams/{team_id}")
        if not resp_existing.get("ok"):
            raise HTTPException(status_code=404, detail="Equipo no encontrado")
        team = resp_existing.get("team", {})

        # Verificar permisos
        if user.get("role_id") != settings.ROLE_SUPER_ADMIN_ID and user.get("id") != team.get("trainer_id"):
            raise HTTPException(status_code=403, detail="No tienes permisos para editar este equipo")

        resp = await client.put(f"/api/teams/{team_id}", data)
        return resp
    except HTTPException:
        raise
    except Exception as e:
        print("❌ Error updating team:", e)
        raise HTTPException(status_code=500, detail="Error actualizando equipo")


@router.delete("/{team_id}")
async def delete_team(team_id: int, user: Dict[str, Any] = Depends(get_current_active_user)):
    """Eliminar equipo. Solo el trainer del equipo o SuperAdmin pueden eliminar."""
    client = DataLayerClient()
    try:
        resp_existing = await client.get(f"/api/teams/{team_id}")
        if not resp_existing.get("ok"):
            raise HTTPException(status_code=404, detail="Equipo no encontrado")
        team = resp_existing.get("team", {})

        if user.get("role_id") != settings.ROLE_SUPER_ADMIN_ID and user.get("id") != team.get("trainer_id"):
            raise HTTPException(status_code=403, detail="No tienes permisos para eliminar este equipo")

        resp = await client.delete(f"/api/teams/{team_id}")
        return resp
    except HTTPException:
        raise
    except Exception as e:
        print("❌ Error deleting team:", e)
        raise HTTPException(status_code=500, detail="Error eliminando equipo")


# ========== MEMBERS ==========
@router.get("/{team_id}/members")
async def list_team_members(team_id: int, limit: int = 100, offset: int = 0):
    """Listar miembros del equipo"""
    client = DataLayerClient()
    try:
        return await client.get(f"/api/teams/{team_id}/members", {"limit": limit, "offset": offset})
    except Exception as e:
        print("❌ Error listing members:", e)
        raise HTTPException(status_code=500, detail="Error listando miembros")


@router.post("/{team_id}/members", status_code=status.HTTP_201_CREATED)
async def add_team_member(
    team_id: int,
    payload: TeamMemberAdd,
    current_user: Dict[str, Any] = Depends(require_any_role(settings.ROLE_TRAINER_ID, settings.ROLE_SUPER_ADMIN_ID))
):
    """Agregar miembro al equipo (solo atletas registrados)"""
    client = DataLayerClient()

    # Validar permiso
    try:
        team_resp = await client.get(f"/api/teams/{team_id}")
        if not team_resp.get("ok"):
            raise HTTPException(status_code=404, detail="Equipo no encontrado")
        team = team_resp.get("team", {})
    except HTTPException:
        raise
    except Exception as e:
        print("❌ Error fetching team:", e)
        raise HTTPException(status_code=500, detail="Error comprobando equipo")

    if current_user.get("role_id") == settings.ROLE_TRAINER_ID:
        if current_user.get("id") != team.get("trainer_id"):
            raise HTTPException(status_code=403, detail="No puedes modificar un equipo que no administras")

    # Validar atleta
    athlete_id = payload.athlete_id
    try:
        athlete_resp = await client.get(f"/api/users/{athlete_id}")
        if not athlete_resp.get("ok"):
            raise HTTPException(status_code=404, detail="Atleta no encontrado")
        
        athlete = athlete_resp.get("user", {})
        if athlete.get("role_id") != settings.ROLE_ATHLETE_ID:
            raise HTTPException(status_code=400, detail="El usuario debe ser un atleta")
    except HTTPException:
        raise
    except Exception as e:
        print("❌ Error verificando atleta:", e)
        raise HTTPException(status_code=500, detail="Error verificando atleta")

    # Agregar
    try:
        add_payload = {
            "athlete_id": athlete_id,
            "join_date": payload.join_date.isoformat() if payload.join_date else None
        }
        resp = await client.post(f"/api/teams/{team_id}/members", add_payload)
        return resp
    except Exception as e:
        print("❌ Error adding member:", e)
        raise HTTPException(status_code=500, detail="Error agregando miembro")


@router.delete("/{team_id}/members/{athlete_id}")
async def remove_team_member(
    team_id: int,
    athlete_id: int,
    current_user: Dict[str, Any] = Depends(get_current_active_user)
):
    """Remover miembro del equipo"""
    client = DataLayerClient()
    try:
        team_resp = await client.get(f"/api/teams/{team_id}")
        if not team_resp.get("ok"):
            raise HTTPException(status_code=404, detail="Equipo no encontrado")
        team = team_resp.get("team", {})

        if current_user.get("role_id") != settings.ROLE_SUPER_ADMIN_ID and current_user.get("id") != team.get("trainer_id"):
            raise HTTPException(status_code=403, detail="No tienes permisos para eliminar miembros")

        resp = await client.delete(f"/api/teams/{team_id}/members/{athlete_id}")
        return resp
    except HTTPException:
        raise
    except Exception as e:
        print("❌ Error removing member:", e)
        raise HTTPException(status_code=500, detail="Error eliminando miembro")