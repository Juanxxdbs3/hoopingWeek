# broker/app/routers/matches.py
from fastapi import APIRouter, HTTPException, status, Depends
from typing import Dict, Any
from pydantic import BaseModel
from app.services.data_layer_client import DataLayerClient
from app.core.deps import get_current_active_user, require_any_role
from app.config.settings import settings

router = APIRouter(prefix="/api/matches", tags=["matches"])

class MatchCreate(BaseModel):
    reservation_id: int
    team1_id: int
    team2_id: int
    is_friendly: bool = False
    championship_id: int | None = None

# Crear match
@router.post("", status_code=status.HTTP_201_CREATED)
async def create_match(
    payload: MatchCreate,
    current_user: Dict[str, Any] = Depends(require_any_role(settings.ROLE_TRAINER_ID, settings.ROLE_SUPER_ADMIN_ID))
):
    client = DataLayerClient()

    # 1) Validar que la reserva exista
    try:
        res = await client.get(f"/api/reservations/{payload.reservation_id}")
        if not res.get("ok"):
            raise HTTPException(status_code=404, detail="Reservation not found")
        reservation = res.get("reservation", {})
    except HTTPException:
        raise
    except Exception as e:
        print("❌ Error fetching reservation:", e)
        raise HTTPException(status_code=500, detail="Error contacting Data Layer")

    # 2) Si se especifica championship_id, validar que exista
    if payload.championship_id:
        try:
            ch = await client.get(f"/api/championships/{payload.championship_id}")
            if not ch.get("ok"):
                raise HTTPException(status_code=404, detail="Championship not found")
        except HTTPException:
            raise
        except Exception as e:
            print("❌ Error fetching championship:", e)
            raise HTTPException(status_code=500, detail="Error contacting Data Layer")

    # 3) Validar activity_type: si es championship la reserva debe ser del tipo adecuado
    act_type = reservation.get("activity_type")
    if payload.championship_id:
        if act_type != "match_championship":
            raise HTTPException(status_code=400, detail="Reservation activity_type is not match_championship")
    else:
        # si no es championship, esperamos match_friendly o similar
        if act_type not in ("match_friendly", "match_championship", "match_official"):
            raise HTTPException(status_code=400, detail=f"Reservation not a match type: {act_type}")

    # 4) Validar que ambos equipos existan
    try:
        t1 = await client.get(f"/api/teams/{payload.team1_id}")
        if not t1.get("ok"):
            raise HTTPException(status_code=404, detail="Team1 not found")
        t2 = await client.get(f"/api/teams/{payload.team2_id}")
        if not t2.get("ok"):
            raise HTTPException(status_code=404, detail="Team2 not found")
    except HTTPException:
        raise
    except Exception as e:
        print("❌ Error fetching teams:", e)
        raise HTTPException(status_code=500, detail="Error contacting Data Layer")

    body = payload.model_dump()
    try:
        created = await client.post("/api/matches", body)
        return created
    except Exception as e:
        print("❌ Error creating match:", e)
        raise HTTPException(status_code=500, detail="Error creating match")

# Listar matches
@router.get("")
async def list_matches(limit: int = 100, offset: int = 0):
    client = DataLayerClient()
    try:
        return await client.get("/api/matches", {"limit": limit, "offset": offset})
    except Exception as e:
        print("❌ Error listing matches:", e)
        raise HTTPException(status_code=500, detail="Error listing matches")

# Obtener por id
@router.get("/{match_id}")
async def get_match(match_id: int):
    client = DataLayerClient()
    try:
        return await client.get(f"/api/matches/{match_id}")
    except Exception as e:
        if "404" in str(e):
            raise HTTPException(status_code=404, detail="Match not found")
        print("❌ Error getting match:", e)
        raise HTTPException(status_code=500, detail="Error getting match")

# Obtener match por reservation
@router.get("/by-reservation/{reservation_id}")
async def get_by_reservation(reservation_id: int):
    client = DataLayerClient()
    try:
        return await client.get(f"/api/matches/by-reservation/{reservation_id}")
    except Exception as e:
        print("❌ Error getting match by reservation:", e)
        raise HTTPException(status_code=500, detail="Error fetching match by reservation")

# Borrar match
@router.delete("/{match_id}")
async def delete_match(match_id: int, current_user: Dict[str, Any] = Depends(get_current_active_user)):
    client = DataLayerClient()
    try:
        return await client.delete(f"/api/matches/{match_id}")
    except Exception as e:
        print("❌ Error deleting match:", e)
        raise HTTPException(status_code=500, detail="Error deleting match")
