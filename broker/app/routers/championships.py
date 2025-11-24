# broker/app/routers/championships.py
from fastapi import APIRouter, HTTPException, status, Depends, Body
from typing import Dict, Any
from app.core.deps import get_current_active_user
from app.services.data_layer_client import DataLayerClient
from app.services.match_orchestrator import MatchOrchestrator
from app.services.reservation_orchestrator import ReservationOrchestrator
from app.services.approval_orchestrator import ApprovalOrchestrator
from app.models.schemas import ChampionshipCreate, ChampionshipUpdate, ChampionshipTeamAdd, ReservationCreateRequest
from app.core.deps import get_current_active_user, require_any_role
from app.config.settings import settings
from datetime import date, datetime, timedelta
from pydantic import BaseModel

router = APIRouter(prefix="/api/championships", tags=["championships"])

# ========== NEW: GET matches enriched for championship ==========
@router.get("/{champ_id}/matches_enriched")
async def get_championship_matches_enriched(champ_id: int, user: Dict = Depends(get_current_active_user)):
    client = DataLayerClient()
    # 1) obtener matches del data-layer
    resp = await client.get(f"/api/matches", params={"championship_id": champ_id})
    if not resp.get("ok"):
        raise HTTPException(status_code=500, detail="Error obteniendo matches desde data layer")

    raw_matches = resp.get("matches", {}).get("data", []) if isinstance(resp.get("matches"), dict) else resp.get("data", [])
    out = []

    # 2) para cada match, obtener reservation y equipos (paralelizar)
    async def enrich(m):
        # - obtener reservation
        resv = None
        if m.get("reservation_id"):
            r = await client.get(f"/api/reservations/{m['reservation_id']}")
            if r.get("ok"):
                resv = r.get("reservation") or r.get("data") or None
        # - obtener teams
        t1 = await client.get(f"/api/teams/{m.get('team1_id')}")
        t2 = await client.get(f"/api/teams/{m.get('team2_id')}")
        team1 = (t1.get("team") or t1.get("data") if t1.get("ok") else None)
        team2 = (t2.get("team") or t2.get("data") if t2.get("ok") else None)

        return {
            "match": m,
            "reservation": resv,
            "team1": team1,
            "team2": team2
        }

    # paralelizar con asyncio.gather
    import asyncio
    enriched = await asyncio.gather(*[enrich(m) for m in raw_matches], return_exceptions=False)
    return {"ok": True, "matches": enriched}


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
    """
    Listar campeonatos. Además, persistir status -> 'active' si start_date <= hoy.
    """
    client = DataLayerClient()
    try:
        res = await client.get("/api/championships", {"limit": limit, "offset": offset})
        # res expected: { "ok": True, "championships": {...} } or similar
        # Intent: si campeonato.start_date <= hoy y status != 'active' -> actualizar a active
        if isinstance(res, dict) and res.get("ok"):
            # navegar estructura flexible
            champs = res.get("championships") or res.get("data") or {}
            # try several shapes
            rows = []
            if isinstance(champs, dict) and champs.get("data"):
                rows = champs.get("data")
            elif isinstance(champs, list):
                rows = champs
            elif isinstance(res.get("championships"), list):
                rows = res.get("championships")
            else:
                # fallback: attempt to handle single-level list
                rows = res.get("championships", {}).get("data", []) if isinstance(res.get("championships"), dict) else []

            today = date.today()
            # If any championship should be activated, do a put to persist
            for c in rows:
                try:
                    start_str = c.get("start_date") or c.get("start")
                    if not start_str:
                        continue
                    # parse date only (YYYY-MM-DD or ISO)
                    start_date = datetime.fromisoformat(start_str).date() if 'T' in start_str or '-' in start_str else date.fromisoformat(start_str)
                    if start_date <= today and c.get("status") != "active":
                        # persist change
                        await client.put(f"/api/championships/{c.get('id')}", {"status": "active"})
                except Exception:
                    # no romper lista por un item
                    continue

        return res
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
    data = {k: v.isoformat() if isinstance(v, (date, datetime)) else v for k, v in payload.model_dump().items() if v is not None}
    
    try:
        resp = await client.put(f"/api/championships/{champ_id}", data)
        return resp
    except Exception as e:
        print("❌ Error updating championship:", e)
        raise HTTPException(status_code=500, detail="Error actualizando campeonato")


@router.delete("/{champ_id}")
async def delete_championship(champ_id: int, current_user: Dict[str, Any] = Depends(get_current_active_user)):
    """
    Elimina championship en cascada:
      1) Obtiene matches del championship (DL /api/matches?championship_id=...)
      2) Para cada match: elimina match y la reservation vinculada (force=true)
      3) Borra el championship
    """
    client = DataLayerClient()
    try:
        # 1) Listar matches del championship
        matches_resp = await client.get("/api/matches", {"championship_id": champ_id, "limit": 1000})
        matches_list = []
        if isinstance(matches_resp, dict):
            # forma: { ok: True, matches: { data: [...] } }
            if matches_resp.get("matches"):
                mblock = matches_resp["matches"]
                matches_list = mblock.get("data") if isinstance(mblock, dict) else mblock
            elif matches_resp.get("data"):
                matches_list = matches_resp["data"]
        # 2) Borrar matches y reservas relacionadas
        for m in matches_list or []:
            try:
                match_id = m.get("id")
                reservation_id = m.get("reservation_id")
                if match_id:
                    await client.delete(f"/api/matches/{match_id}")
                if reservation_id:
                    # force delete reservation to remove it entirely
                    await client.delete(f"/api/reservations/{reservation_id}?force=true")
            except Exception as exc:
                # log y continuar
                print(f"Warning deleting match/reservation for championship {champ_id}: {exc}")
                continue
        # 3) Eliminar championship en DL
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


# ========== NEW: GET matches for championship (proxy) ==========
@router.get("/{champ_id}/matches")
async def list_championship_matches(champ_id: int, limit: int = 100, offset: int = 0):
    """Listar partidos del campeonato"""
    client = DataLayerClient()
    try:
        return await client.get("/api/matches", {"championship_id": champ_id, "limit": limit, "offset": offset})
    except Exception as e:
        print("❌ Error listing matches for championship:", e)
        raise HTTPException(status_code=500, detail="Error listando matches del campeonato")


# ========== NEW: Create a match for championship (creates reservation validated + match) ==========
@router.post("/{champ_id}/matches", status_code=status.HTTP_201_CREATED)
async def create_championship_match(
    champ_id: int,
    payload: Dict[str, Any] = Body(...),
    current_user: Dict[str, Any] = Depends(require_any_role(settings.ROLE_TRAINER_ID, settings.ROLE_SUPER_ADMIN_ID))
):
    """
    payload expected (from frontend): {
      "field_id": int,
      "team1_id": int,
      "team2_id": int,
      "start_datetime": "YYYY-MM-DD HH:MM:SS" or ISO,
      "duration": int (hours),
      "notes": str (optional)
    }
    Flow:
      1) verificar championship.exists && championship.status == 'planning'
      2) crear reserva validated con activity_type='match_championship' y applicant_id = current_user['id']
      3) aprobar automáticamente la reserva (important)
      4) crear match ligado a la reserva y championship_id
    """
    client = DataLayerClient()
    # 1) verificar championship
    try:
        ch = await client.get(f"/api/championships/{champ_id}")
        if not ch.get("ok"):
            raise HTTPException(status_code=404, detail="Campeonato no encontrado")
        champ = ch.get("championship") or ch.get("data") or {}
        if champ.get("status") != "planning":
            raise HTTPException(status_code=400, detail="Solo se pueden agregar matches a championships en estado 'planning'")
    except HTTPException:
        raise
    except Exception as e:
        print("❌ Error fetching championship:", e)
        raise HTTPException(status_code=500, detail="Error contacting Data Layer")

    # 2) build reservation payload
    reservation = None
    try:
        start_str = payload.get("start_datetime")
        if not start_str:
            raise HTTPException(status_code=400, detail="start_datetime es requerido")
        # parse start
        try:
            start_dt = datetime.fromisoformat(start_str)
        except Exception:
            start_dt = datetime.strptime(start_str, "%Y-%m-%d %H:%M:%S")
        duration_hours = int(payload.get("duration", 1)) or 1
        end_dt = start_dt + timedelta(hours=duration_hours)

        reservation_payload = ReservationCreateRequest(
            field_id=int(payload.get("field_id")),
            applicant_id=int(current_user.get("id")),
            activity_type="match_championship",
            start_datetime=start_dt,
            end_datetime=end_dt,
            participants=[],
            notes=payload.get("notes")
        )

        orchestrator = ReservationOrchestrator()
        res = await orchestrator.create_with_validation(reservation_payload)
        if not res.get("ok"):
            # forward validation errors
            raise HTTPException(status_code=400, detail=res)

        reservation = res["reservation"]

        # 3) Intentar aprobar automáticamente la reserva asociada
        try:
            approval_orch = ApprovalOrchestrator()
            approve_result = await approval_orch.approve_reservation(
                reservation_id=int(reservation["id"]),
                approver_id=int(current_user.get("id")),
                note="Auto-approved: championship match"
            )
            if not approve_result.get("ok"):
                # fallback: intentar forzar el status directamente en Data Layer
                try:
                    put_resp = await client.put(f"/api/reservations/{reservation['id']}", {"status": "approved"})
                    if not (isinstance(put_resp, dict) and put_resp.get("ok")):
                        # algo falló; limpiar y devolver error
                        await client.delete(f"/api/reservations/{reservation['id']}?force=true")
                        raise HTTPException(status_code=500, detail={"message": "No se pudo aprobar la reserva automáticamente", "detail": approve_result})
                except Exception as put_exc:
                    # cleanup and bubble
                    await client.delete(f"/api/reservations/{reservation['id']}?force=true")
                    print("❌ Fallback put approval failed:", put_exc)
                    raise HTTPException(status_code=500, detail="No se pudo aprobar la reserva de championship (fallback fallido)")
        except HTTPException:
            # si ApprovalOrchestrator lanzó un HTTPException, borramos la reserva y re-lanzamos
            if reservation and reservation.get("id"):
                try:
                    await client.delete(f"/api/reservations/{reservation['id']}?force=true")
                except Exception:
                    pass
            raise
        except Exception as e:
            # error inesperado en aprobación: limpiar y devolver
            print("❌ Error auto-aprobar reserva:", e)
            if reservation and reservation.get("id"):
                try:
                    await client.delete(f"/api/reservations/{reservation['id']}?force=true")
                except Exception:
                    pass
            raise HTTPException(status_code=500, detail="Error auto-aprobando la reserva para el match de championship")

        # 4) crear match usando MatchOrchestrator (vincular championship_id)
        match_orch = MatchOrchestrator()
        match_body = {
            "reservation_id": int(reservation["id"]),
            "team1_id": int(payload.get("team1_id")),
            "team2_id": int(payload.get("team2_id")),
            "is_friendly": False,
            "championship_id": champ_id
        }
        match_resp = await match_orch.create(match_body)

        return {"ok": True, "reservation": reservation, "match": match_resp}
    except HTTPException:
        raise
    except Exception as e:
        # en caso de error general: intentar cleanup de reservation si fue creada
        print("❌ Error creating championship match:", e)
        if reservation and reservation.get("id"):
            try:
                await client.delete(f"/api/reservations/{reservation['id']}?force=true")
            except Exception:
                pass
        raise HTTPException(status_code=500, detail="Error creando match para championship")
