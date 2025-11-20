# broker/app/routers/auth.py
from fastapi import APIRouter, HTTPException, status, Depends
from pydantic import BaseModel
from datetime import datetime, timedelta
import jwt
from app.core.deps import get_current_user
from app.services.auth_orchestrator import AuthOrchestrator
from app.config.settings import settings

router = APIRouter(prefix="/api/auth", tags=["auth"])
orchestrator = AuthOrchestrator()

class LoginRequest(BaseModel):
    identifier: str
    password: str

@router.post("/login")
async def login(payload: LoginRequest):
    """
    Login: recibe identifier (email o id) y password.
    Llama a Data Layer via AuthOrchestrator, y si ok genera JWT.
    """
    try:
        result = await orchestrator.authenticate(payload.identifier, payload.password)
    except Exception:
        # Error de comunicación con Data Layer
        raise HTTPException(status_code=status.HTTP_502_BAD_GATEWAY, detail="Error comunicándose con Data Layer")

    if not result.get("ok"):
        # devolver 401 con el mensaje de la data layer si aplica
        # Normalmente Data Layer devolverá ok:false y un 'error' o no.
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail=result.get("error", "Invalid credentials"))

    user = result.get("user")
    now = datetime.utcnow()
    expire = now + timedelta(minutes=int(settings.jwt_exp_minutes))
    token_payload = {
        "sub": str(user.get("id")),
        "role_id": user.get("role_id"),
        "iat": int(now.timestamp()),
        "exp": int(expire.timestamp())
    }
    token = jwt.encode(token_payload, settings.jwt_secret, algorithm="HS256")

    return {
        "ok": True,
        "access_token": token,
        "token_type": "bearer",
        "expires_at": expire.isoformat() + "Z",
        "user": user
    }


@router.get("/me")
async def me(payload = Depends(get_current_user)):
    return {"ok": True, "payload": payload}
