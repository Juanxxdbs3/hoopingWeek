# broker/app/core/deps.py
from fastapi import Depends, HTTPException, status, Security
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from app.services.data_layer_client import DataLayerClient
from app.config.settings import settings
import jwt
from typing import Dict, Any
import time

security = HTTPBearer()

# ---------------------------------------------------------
# 1) Obtener usuario desde el token JWT
# ---------------------------------------------------------
async def get_current_user(
    creds: HTTPAuthorizationCredentials = Security(security)
) -> Dict[str, Any]:
    """
    Decodifica el JWT y devuelve el payload.
    Usamos Security(...) para que FastAPI lo refleje en OpenAPI (/docs) como esquema de seguridad.
    """
    token = creds.credentials

    try:
        payload = jwt.decode(
            token,
            settings.jwt_secret,
            algorithms=["HS256"],
            options={"verify_iat": False}
        )
    except jwt.ExpiredSignatureError:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Token expired"
        )
    except jwt.InvalidTokenError as e:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail=f"Invalid token: {str(e)}"
        )

    # El payload contiene: sub (user_id), role_id, iat, exp
    return payload

# ---------------------------------------------------------
# 2) Cache de usuarios para no consultar Data Layer siempre
# ---------------------------------------------------------
# cache en memoria: { user_id: { "user": {...}, "expires": timestamp } }
USER_CACHE = {}
CACHE_TTL_SECONDS = 30   # puedes subirlo a 60 o 120 si quieres, lo normal es 30


async def _fetch_user_from_data_layer(user_id: int) -> Dict[str, Any]:
    """Hace la consulta real al Data Layer."""
    client = DataLayerClient()
    response = await client.get(f"/api/users/{user_id}")

    if not response.get("ok"):
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="User not found in database"
        )

    return response["user"]


async def get_current_user_full(
    payload: Dict[str, Any] = Depends(get_current_user)
) -> Dict[str, Any]:
    """
    Obtiene el usuario, usando cache si es posible.
    Si está en cache y vigente → lo usa.
    Si no → consulta DataLayer y refresca cache.
    """
    user_id = int(payload.get("sub"))
    now = time.time()

    # --- Si está cacheado y no expiró ---
    cached = USER_CACHE.get(user_id)
    if cached and cached["expires"] > now:
        user = cached["user"]
        # adjuntar info del token (sin romper estructura del user)
        user["_token_role_id"] = payload.get("role_id")
        user["_token_exp"] = payload.get("exp")
        return user

    # --- Si no, obtener del Data Layer ---
    user = await _fetch_user_from_data_layer(user_id)

    # Guardar en cache con TTL
    USER_CACHE[user_id] = {
        "user": user,
        "expires": now + CACHE_TTL_SECONDS
    }

    # Adjuntar info del token
    user["_token_role_id"] = payload.get("role_id")
    user["_token_exp"] = payload.get("exp")

    return user


# ---------------------------------------------------------
# 3) Verificar que el usuario está ACTIVO
# ---------------------------------------------------------
async def get_current_active_user(
    user: Dict[str, Any] = Depends(get_current_user_full)
) -> Dict[str, Any]:
    """
    Valida que el usuario tenga state_id = 1 (active).
    Usa la versión cacheada.
    """
    if user.get("state_id") != 1:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="User account is inactive"
        )
    return user

# ---------------------------------------------------------
# 4) Helper: exigir rol específico
# ---------------------------------------------------------
def require_role(required_role_id: int):
    """
    Factory que crea un dependency que valida rol específico.
    Uso: user = Depends(require_role(settings.ROLE_TRAINER_ID))
    """
    async def role_checker(user: Dict[str, Any] = Depends(get_current_active_user)):
        if user.get("role_id") != required_role_id:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail=f"Access denied: requires role_id={required_role_id}"
            )
        return user
    return role_checker


# ---------------------------------------------------------
# 5) Helper: exigir SuperAdmin
# ---------------------------------------------------------
async def require_super_admin(
    user: Dict[str, Any] = Depends(get_current_active_user)
) -> Dict[str, Any]:
    """
    Valida que el usuario sea SuperAdmin (role_id = 4).
    """
    if user.get("role_id") != settings.ROLE_SUPER_ADMIN_ID:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="SuperAdmin privilege required"
        )
    return user


# ---------------------------------------------------------
# 6) Helper: permitir múltiples roles
# ---------------------------------------------------------
def require_any_role(*allowed_roles: int):
    """
    Permite que cualquiera de los roles especificados acceda.
    Uso: user = Depends(require_any_role(settings.ROLE_TRAINER_ID, settings.ROLE_SUPER_ADMIN_ID))
    """
    async def multi_role_checker(user: Dict[str, Any] = Depends(get_current_active_user)):
        if user.get("role_id") not in allowed_roles:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail=f"Access denied: requires one of roles {allowed_roles}"
            )
        return user
    return multi_role_checker
