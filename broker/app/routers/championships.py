# broker/app/routers/championships.py
from fastapi import APIRouter, HTTPException, status, Depends
from typing import Dict, Any
from app.services.data_layer_client import DataLayerClient
from app.models.schemas import ChampionshipCreate, ChampionshipUpdate, ChampionshipTeamAdd
from app.core.deps import get_current_active_user, require_any_role
from app.config.settings import settings
from datetime import date  # <-- añadir esto si faltaba


router = APIRouter(prefix="/api/championships", tags=["championships"])


@router.post("", status_code=status.HTTP_201_CREATED)
async def create_championship(
    payload: ChampionshipCreate,
    current_user: Dict[str, Any] = Depends(require_any_role(settings.ROLE_TRAINER_ID, settings.ROLE_SUPER_ADMIN_ID))
):
    """Crear campeonato. Trainer se asigna automáticamente como organizer_id."""
    client = DataLayerClient()
    body = payload.model_dump()
    
    # Si es trainer, forzar su ID como organizer_id
    if current_user.get("role_id") == settings.ROLE_TRAINER_ID:
        body["organizer_id"] = current_user.get("id")

    # Convertir dates a strings
    body["start_date"] = body["start_date"].isoformat()
    body["end_date"] = body["end_date"].isoformat()

    try:
        resp = await client.post("/api/championships", body)
        return resp
    except Exception as e:
        print("❌ Error creating championship:", e)
        raise HTTPException(status_code=500, detail="Error creando campeonato")


@router.get("")
async def list_championships(limit: int = 100, offset: int = 0):
    """Listar campeonatos"""
    client = DataLayerClient()
    try:
        return await client.get("/api/championships", {"limit": limit, "offset": offset})
    except Exception as e:
        print("❌ Error listing championships:", e)
        raise HTTPException(status_code=500, detail="Error listando campeonatos")


@router.get("/{champ_id}")
async def get_championship(champ_id: int):
    """Obtener un campeonato por ID"""
    client = DataLayerClient()
    try:
        return await client.get(f"/api/championships/{champ_id}")
    except Exception as e:
        if "404" in str(e):
            raise HTTPException(status_code=404, detail="Campeonato no encontrado")
        raise HTTPException(status_code=500, detail="Error obteniendo campeonato")


@router.put("/{champ_id}")
async def update_championship(
    champ_id: int,
    payload: ChampionshipUpdate,
    current_user: Dict[str, Any] = Depends(get_current_active_user)
):
    """Actualizar campeonato"""
    client = DataLayerClient()
    data = {k: v.isoformat() if isinstance(v, date) else v for k, v in payload.model_dump().items() if v is not None}
    
    try:
        resp = await client.put(f"/api/championships/{champ_id}", data)
        return resp
    except Exception as e:
        print("❌ Error updating championship:", e)
        raise HTTPException(status_code=500, detail="Error actualizando campeonato")


@router.delete("/{champ_id}")
async def delete_championship(champ_id: int, current_user: Dict[str, Any] = Depends(get_current_active_user)):
    """Eliminar campeonato"""
    client = DataLayerClient()
    try:
        resp = await client.delete(f"/api/championships/{champ_id}")
        return resp
    except Exception as e:
        print("❌ Error deleting championship:", e)
        raise HTTPException(status_code=500, detail="Error eliminando campeonato")


# ========== TEAMS ==========
@router.post("/{champ_id}/teams", status_code=status.HTTP_201_CREATED)
async def add_team_to_championship(
    champ_id: int,
    payload: ChampionshipTeamAdd,
    current_user: Dict[str, Any] = Depends(get_current_active_user)
):
    """Agregar equipo al campeonato"""
    client = DataLayerClient()
    try:
        # Validar que existen
        ch = await client.get(f"/api/championships/{champ_id}")
        if not ch.get("ok"):
            raise HTTPException(status_code=404, detail="Campeonato no encontrado")

        t = await client.get(f"/api/teams/{payload.team_id}")
        if not t.get("ok"):
            raise HTTPException(status_code=404, detail="Equipo no encontrado")

        resp = await client.post(f"/api/championships/{champ_id}/teams", {"team_id": payload.team_id})
        return resp
    except HTTPException:
        raise
    except Exception as e:
        print("❌ Error adding team to championship:", e)
        raise HTTPException(status_code=500, detail="Error agregando equipo al campeonato")


@router.get("/{champ_id}/teams")
async def list_championship_teams(champ_id: int):
    """Listar equipos del campeonato"""
    client = DataLayerClient()
    try:
        return await client.get(f"/api/championships/{champ_id}/teams")
    except Exception as e:
        print("❌ Error listing championship teams:", e)
        raise HTTPException(status_code=500, detail="Error listando equipos del campeonato")


@router.delete("/{champ_id}/teams/{team_id}")
async def remove_team_from_championship(champ_id: int, team_id: int):
    """Remover equipo del campeonato"""
    client = DataLayerClient()
    try:
        return await client.delete(f"/api/championships/{champ_id}/teams/{team_id}")
    except Exception as e:
        print("❌ Error removing team from championship:", e)
        raise HTTPException(status_code=500, detail="Error eliminando equipo del campeonato")