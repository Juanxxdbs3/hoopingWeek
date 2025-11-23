# broker/app/routers/championships.py
from fastapi import APIRouter, HTTPException, status, Depends
from typing import Dict, Any
from app.services.data_layer_client import DataLayerClient
from app.services.reservation_orchestrator import ReservationOrchestrator
from app.services.match_orchestrator import MatchOrchestrator
from app.models.schemas import ChampionshipCreate, ChampionshipUpdate, ChampionshipTeamAdd
from app.core.deps import get_current_active_user, require_any_role
from app.config.settings import settings
from datetime import date, datetime
from fastapi import Body
from pydantic import BaseModel


router = APIRouter(prefix="/api/championships", tags=["championships"])


# ---------------------------------------------------------------------
# Payload para crear match dentro de un campeonato (orquestado por broker)
class ChampionshipMatchCreate(BaseModel):
    field_id: int
    team1_id: int
    team2_id: int
    start_datetime: str   # formato esperado: "YYYY-MM-DD HH:MM:SS"
    duration: int = 1     # horas (opcional, default 1)
    notes: str | None = None
# ---------------------------------------------------------------------

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


@router.post("/{champ_id}/matches", status_code=status.HTTP_201_CREATED)
async def create_championship_match(
    champ_id: int,
    payload: ChampionshipMatchCreate = Body(...),
    current_user: Dict[str, Any] = Depends(require_any_role(settings.ROLE_TRAINER_ID, settings.ROLE_SUPER_ADMIN_ID))
):
    """
    Orquesta: crea primero la reserva (validada) y luego crea el match asociado.
    Si la creación del match falla, elimina la reserva creada (rollback).
    """
    client = DataLayerClient()

    # 1) Validar que el campeonato existe
    try:
        ch = await client.get(f"/api/championships/{champ_id}")
        if not ch.get("ok"):
            raise HTTPException(status_code=404, detail="Campeonato no encontrado")
    except HTTPException:
        raise
    except Exception as e:
        print("❌ Error fetching championship:", e)
        raise HTTPException(status_code=500, detail="Error contacting Data Layer for championship")

    # 2) Validar equipos y campo existen
    try:
        t1 = await client.get(f"/api/teams/{payload.team1_id}")
        if not t1.get("ok"):
            raise HTTPException(status_code=404, detail="Team1 not found")
        t2 = await client.get(f"/api/teams/{payload.team2_id}")
        if not t2.get("ok"):
            raise HTTPException(status_code=404, detail="Team2 not found")

        f = await client.get(f"/api/fields/{payload.field_id}")
        if not f.get("ok"):
            raise HTTPException(status_code=404, detail="Field not found")
    except HTTPException:
        raise
    except Exception as e:
        print("❌ Error validating teams/field:", e)
        raise HTTPException(status_code=500, detail="Error contacting Data Layer for teams/field")

    # 3) Crear la reserva usando el Orchestrator (aplica reglas DS/DCC)
    ro = ReservationOrchestrator()
    try:
        # transformar start_datetime a objeto datetime (ReservationOrchestrator espera datetime)
        dt = None
        try:
            dt = datetime.strptime(payload.start_datetime, "%Y-%m-%d %H:%M:%S")
        except Exception:
            raise HTTPException(status_code=400, detail="start_datetime debe usar formato 'YYYY-MM-DD HH:MM:SS'")

        # construir request para reserva
        from app.models.schemas import ReservationCreateRequest  # import tardío para evitar ciclos
        reservation_req = ReservationCreateRequest(
            field_id=payload.field_id,
            applicant_id=current_user["id"],
            activity_type="match_championship",
            start_datetime=dt,
            end_datetime=dt.replace(hour=(dt.hour + payload.duration) % 24) if payload.duration else dt
        )

        # Nota: end_datetime calculado de forma simple por horas; si cruzas día, ajusta según reglas.
        result = await ro.create_with_validation(reservation_req)
        if not result.get("ok"):
            # devolver detalle de errores del orchestrator
            raise HTTPException(status_code=400, detail=result)
        reservation = result.get("reservation")
    except HTTPException:
        raise
    except Exception as e:
        print("❌ Error creating reservation via orchestrator:", e)
        raise HTTPException(status_code=500, detail="Error creating reservation")

    # 4) Crear match en Data Layer (usamos MatchOrchestrator)
    mo = MatchOrchestrator()
    match_payload = {
        "reservation_id": reservation["id"],
        "team1_id": payload.team1_id,
        "team2_id": payload.team2_id,
        "is_friendly": False,
        "championship_id": champ_id,
        "start_datetime": payload.start_datetime,
        "duration": payload.duration,
        "notes": payload.notes
    }

    try:
        match_resp = await mo.create(match_payload)
        # esperamos {'ok': True, 'match': {...}}
        if not match_resp.get("ok"):
            # rollback: eliminar reserva creada
            try:
                await client.delete(f"/api/reservations/{reservation['id']}?force=true")
            except Exception as e_del:
                print("⚠️ Error during rollback delete reservation:", e_del)
            raise HTTPException(status_code=500, detail={"ok": False, "error": "Error creating match", "detail": match_resp})
    except HTTPException:
        # si ya fue lanzado, propágalo
        raise
    except Exception as e:
        # fallback: rollback y 500
        print("❌ Error creating match:", e)
        try:
            await client.delete(f"/api/reservations/{reservation['id']}?force=true")
        except Exception as e_del:
            print("⚠️ Error during rollback delete reservation:", e_del)
        raise HTTPException(status_code=500, detail="Error creating match")

    # 5) Éxito: devolver ambos objetos
    return {
        "ok": True,
        "reservation": reservation,
        "match": match_resp.get("match", match_resp)
    }


@router.delete("/{champ_id}/teams/{team_id}")
async def remove_team_from_championship(champ_id: int, team_id: int):
    """Remover equipo del campeonato"""
    client = DataLayerClient()
    try:
        return await client.delete(f"/api/championships/{champ_id}/teams/{team_id}")
    except Exception as e:
        print("❌ Error removing team from championship:", e)
        raise HTTPException(status_code=500, detail="Error eliminando equipo del campeonato")