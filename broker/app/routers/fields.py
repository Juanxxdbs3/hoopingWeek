from fastapi import APIRouter, HTTPException, Query, status, Depends, Body
from datetime import date
from typing import Optional, Dict, Any
from app.services.availability_orchestrator import AvailabilityOrchestrator
from app.services.data_layer_client import DataLayerClient
from app.models.schemas import (
    FieldCreate, FieldUpdate, FieldStateUpdate,
    OperatingHourCreate, FieldExceptionCreate
)
from app.core.deps import get_current_active_user, require_any_role
from app.config.settings import settings
import re
from typing import List, Dict, Any

# Expresiones regulares para validar tiempos
TIME_3_RE = re.compile(r'^\d{1,2}:\d{2}:\d{2}$')  # HH:MM:SS o H:MM:SS
TIME_2_RE = re.compile(r'^\d{1,2}:\d{2}$')        # H:MM o HH:MM

router = APIRouter(prefix="/api/fields", tags=["fields"])

def _ensure_hh_mm_ss(t: str | None) -> str | None:
    """Normaliza 'H:M' o 'HH:MM' o 'HH:MM:SS' -> 'HH:MM:SS'. Devuelve None si t es None."""
    if t is None:
        return None
    t = t.strip()
    if TIME_3_RE.match(t):
        return t
    if TIME_2_RE.match(t):
        return t + ":00"
    # intento tolerante: separar por ':' y rellenar
    parts = t.split(':')
    if len(parts) >= 2:
        hh = parts[0].zfill(2)
        mm = parts[1].zfill(2)
        ss = parts[2].zfill(2) if len(parts) > 2 else "00"
        return f"{hh}:{mm}:{ss}"
    # formato inválido — lanzar para que el endpoint capture y devuelva 400
    raise ValueError(f"Formato de tiempo inválido: '{t}'")

def _normalize_operating_hours_shape(raw: Any) -> List[Dict[str, Any]]:
    """
    Acepta varias formas que pueda devolver el data-layer y devuelve:
    [{day_of_week:int, start_time:str(HH:MM:SS), end_time:str(HH:MM:SS)}]
    """
    out = []
    if not raw:
        return out

    # Si está en bloque { data: [...] } o { operating_hours: { data: [...] } }
    if isinstance(raw, dict):
        # check nested shapes
        if raw.get("data"):
            raw_list = raw.get("data")
        elif raw.get("operating_hours"):
            raw_list = raw.get("operating_hours")
        else:
            # fallback: si tiene keys day_of_week => single object
            if 'day_of_week' in raw:
                raw_list = [raw]
            else:
                # no sabemos, devolver vacío
                return out
    else:
        raw_list = raw

    if not isinstance(raw_list, list):
        raw_list = [raw_list]

    for item in raw_list:
        try:
            day = item.get("day_of_week") if isinstance(item, dict) else None
            if day is None:
                day = item.get("day") or item.get("weekday") or item.get("dow")
            start = item.get("start_time") or item.get("open_time") or item.get("start") or item.get("open")
            end = item.get("end_time") or item.get("close_time") or item.get("end") or item.get("close")
            start = _ensure_hh_mm_ss(start) if start else None
            end = _ensure_hh_mm_ss(end) if end else None
            out.append({
                "day_of_week": int(day) if day is not None else None,
                "start_time": start,
                "end_time": end
            })
        except Exception:
            # ignorar item mal formado y seguir
            continue
    return out


# ==========================================
#  LECTURA (PÚBLICO O AUTENTICADO)
# ==========================================

@router.get("")
async def list_fields(
    state: Optional[str] = None,
    location: Optional[str] = None,
    sport: Optional[str] = None,
    limit: int = Query(100, le=500),
    offset: int = Query(0, ge=0)
):
    """Listar campos con filtros"""
    client = DataLayerClient()
    params = {"limit": limit, "offset": offset}
    if state: params["state"] = state
    if location: params["location"] = location
    if sport: params["sport"] = sport
    
    try:
        return await client.get("/api/fields", params)
    except Exception as e:
        print(f"❌ Error listando campos: {e}")
        raise HTTPException(status_code=500, detail="Error al listar campos")

@router.get("/{field_id}")
async def get_field(field_id: int):
    """Obtener un campo por ID"""
    client = DataLayerClient()
    try:
        return await client.get(f"/api/fields/{field_id}")
    except Exception as e:
        if "404" in str(e):
            raise HTTPException(status_code=404, detail="Campo no encontrado")
        raise HTTPException(status_code=500, detail="Error obteniendo campo")

@router.get("/{field_id}/availability")
async def get_field_availability(
    field_id: int,
    date: date = Query(..., description="YYYY-MM-DD")
):
    """Obtener disponibilidad detallada (slots)"""
    orchestrator = AvailabilityOrchestrator()
    result = await orchestrator.get_field_availability(field_id, date)
    if not result.get("ok"):
        raise HTTPException(status_code=400, detail=result.get("message"))
    return result

@router.get("/{field_id}/operating-hours")
async def get_operating_hours(field_id: int):
    """Obtener horarios regulares de un campo - devuelve forma consistente."""
    client = DataLayerClient()
    try:
        resp = await client.get(f"/api/fields/{field_id}/operating-hours")
    except Exception as e:
        print(f"❌ Error obteniendo horarios: {e}")
        raise HTTPException(status_code=500, detail="Error obteniendo horarios")

    # normalizar estructura (variaciones que pueda devolver el data-layer)
    raw = None
    if isinstance(resp, dict):
        # priorizar claves comunes
        raw = resp.get("operating_hours") or resp.get("hours") or resp.get("data") or resp.get("result") or resp
    else:
        raw = resp

    normalized = _normalize_operating_hours_shape(raw)
    return {"ok": True, "operating_hours": normalized}


@router.get("/{field_id}/exceptions/range")
async def get_exceptions_range(
    field_id: int, 
    start_date: date, 
    end_date: date
):
    """Obtener excepciones en un rango de fechas"""
    client = DataLayerClient()
    try:
        params = {
            "start_date": start_date.isoformat(),
            "end_date": end_date.isoformat()
        }
        return await client.get(f"/api/fields/{field_id}/exceptions/range", params)
    except Exception as e:
        print(f"❌ Error obteniendo excepciones: {e}")
        raise HTTPException(status_code=500, detail="Error obteniendo excepciones")


# ==========================================
#  ESCRITURA (SOLO ADMINS Y MANAGERS)
# ==========================================

# Roles permitidos: Super Admin (4) y Field Manager (3)
PERMITTED_ROLES = [settings.ROLE_FIELD_MANAGER_ID, settings.ROLE_SUPER_ADMIN_ID]

@router.post("", status_code=status.HTTP_201_CREATED)
async def create_field(
    payload: FieldCreate,
    user: Dict[str, Any] = Depends(require_any_role(settings.ROLE_SUPER_ADMIN_ID)) # Solo SuperAdmin crea
):
    """Crear nuevo escenario"""
    client = DataLayerClient()
    try:
        return await client.post("/api/fields", payload.model_dump())
    except Exception as e:
        print(f"❌ Error creando campo: {e}")
        raise HTTPException(status_code=500, detail="Error creando campo")

@router.put("/{field_id}")
async def update_field(
    field_id: int,
    payload: FieldUpdate,
    user: Dict[str, Any] = Depends(require_any_role(*PERMITTED_ROLES))
):
    """Actualizar datos del escenario"""
    client = DataLayerClient()
    data = {k: v for k, v in payload.model_dump().items() if v is not None}
    try:
        return await client.put(f"/api/fields/{field_id}", data)
    except Exception as e:
        print(f"❌ Error actualizando campo: {e}")
        raise HTTPException(status_code=500, detail="Error actualizando campo")

@router.patch("/{field_id}/state")
async def change_field_state(
    field_id: int,
    payload: FieldStateUpdate,
    user: Dict[str, Any] = Depends(require_any_role(*PERMITTED_ROLES))
):
    """Cambiar estado (active/inactive/maintenance)"""
    client = DataLayerClient()
    try:
        return await client.patch(f"/api/fields/{field_id}/state", {"state": payload.state})
    except Exception as e:
        print(f"❌ Error cambiando estado: {e}")
        raise HTTPException(status_code=500, detail="Error cambiando estado")

@router.delete("/{field_id}")
async def delete_field(
    field_id: int,
    force: bool = False,
    user: Dict[str, Any] = Depends(require_any_role(settings.ROLE_SUPER_ADMIN_ID))
):
    """Eliminar escenario"""
    client = DataLayerClient()
    endpoint = f"/api/fields/{field_id}"
    if force:
        endpoint += "?force=true"
    try:
        return await client.delete(endpoint)
    except Exception as e:
        # Capturar el error 409 del Data Layer y pasarlo al frontend
        if "409" in str(e) or "Integrity constraint" in str(e):
             raise HTTPException(status_code=409, detail="No se puede eliminar: tiene reservas asociadas.")
        print(f"❌ Error eliminando campo: {e}")
        raise HTTPException(status_code=500, detail="Error eliminando campo")

# ==========================================
#  GESTIÓN DE HORARIOS Y EXCEPCIONES
# ==========================================

@router.post("/{field_id}/operating-hours", status_code=status.HTTP_201_CREATED)
async def create_operating_hour(
    field_id: int,
    payload: OperatingHourCreate,
    user: Dict[str, Any] = Depends(require_any_role(*PERMITTED_ROLES))
):
    """Definir horario regular — valida duplicados por day_of_week y normaliza horarios."""
    client = DataLayerClient()

    # normalizar el payload a dict
    try:
        body = payload.model_dump()
    except Exception as e:
        raise HTTPException(status_code=400, detail="Payload inválido")

    # Normalizar tiempos a HH:MM:SS y validar orden
    try:
        body["open_time"] = _ensure_hh_mm_ss(body.get("open_time"))
        body["close_time"] = _ensure_hh_mm_ss(body.get("close_time"))
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

    if body["open_time"] >= body["close_time"]:
        raise HTTPException(status_code=400, detail="close_time debe ser mayor que open_time")

    # 1) Consultar horarios existentes para detectar duplicados por day_of_week
    try:
        existing_resp = await client.get(f"/api/fields/{field_id}/operating-hours")
        existing_block = None
        if isinstance(existing_resp, dict):
            existing_block = existing_resp.get("operating_hours") or existing_resp.get("data") or existing_resp.get("hours") or existing_resp
        else:
            existing_block = existing_resp
        existing_list = _normalize_operating_hours_shape(existing_block)
    except Exception as e:
        # si Data Layer falla, avisar
        print(f"⚠️ No se pudo verificar horarios existentes: {e}")
        existing_list = []

    # check duplicate day
    if any((h.get("day_of_week") == body.get("day_of_week")) for h in existing_list if h.get("day_of_week") is not None):
        raise HTTPException(status_code=409, detail="Horario para ese día ya existe. Elimina o actualiza el existente.")

    # 2) Crear en Data Layer
    try:
        return await client.post(f"/api/fields/{field_id}/operating-hours", body)
    except Exception as e:
        print(f"❌ Error creando horario: {e}")
        raise HTTPException(status_code=500, detail="Error creando horario")


@router.delete("/{field_id}/operating-hours/{day_of_week}")
async def delete_operating_hour(
    field_id: int,
    day_of_week: int,
    user: Dict[str, Any] = Depends(require_any_role(*PERMITTED_ROLES))
):
    """Eliminar horario regular de un día"""
    client = DataLayerClient()
    try:
        return await client.delete(f"/api/fields/{field_id}/operating-hours/{day_of_week}")
    except Exception as e:
        print(f"❌ Error eliminando horario: {e}")
        raise HTTPException(status_code=500, detail="Error eliminando horario")

@router.post("/{field_id}/exceptions", status_code=status.HTTP_201_CREATED)
async def create_exception(
    field_id: int,
    payload: FieldExceptionCreate,
    user: Dict[str, Any] = Depends(require_any_role(*PERMITTED_ROLES))
):
    """Definir excepción de horario — normaliza date y tiempos a ISO / HH:MM:SS."""
    client = DataLayerClient()
    body = payload.model_dump()
    # fecha -> iso
    body["date"] = body["date"].isoformat()

    # normalizar open/close si vienen
    try:
        if body.get("open_time"):
            body["open_time"] = _ensure_hh_mm_ss(body.get("open_time"))
        if body.get("close_time"):
            body["close_time"] = _ensure_hh_mm_ss(body.get("close_time"))
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

    # si overrides_regular es true y se entregaron ambos horarios, validar orden
    if body.get("overrides_regular") and body.get("open_time") and body.get("close_time"):
        if body["open_time"] >= body["close_time"]:
            raise HTTPException(status_code=400, detail="close_time debe ser mayor que open_time")

    try:
        return await client.post(f"/api/fields/{field_id}/exceptions", body)
    except Exception as e:
        print(f"❌ Error creando excepción: {e}")
        raise HTTPException(status_code=500, detail="Error creando excepción")


@router.delete("/exceptions/{exception_id}")
async def delete_exception(
    exception_id: int,
    user: Dict[str, Any] = Depends(require_any_role(*PERMITTED_ROLES))
):
    """Eliminar excepción por ID"""
    client = DataLayerClient()
    try:
        return await client.delete(f"/api/exceptions/{exception_id}")
    except Exception as e:
        print(f"❌ Error eliminando excepción: {e}")
        raise HTTPException(status_code=500, detail="Error eliminando excepción")