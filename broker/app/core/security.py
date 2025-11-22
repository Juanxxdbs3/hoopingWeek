from datetime import datetime, timedelta
import jwt
from app.config.settings import settings

def create_access_token(data: dict) -> str:
    """
    Crea un JWT token con los datos proporcionados
    
    Args:
        data: dict con { "sub": user_id, "email": ..., "role_id": ... }
    
    Returns:
        str: JWT token codificado
    """
    now = datetime.utcnow()
    expire = now + timedelta(minutes=int(settings.jwt_exp_minutes))
    
    payload = {
        **data,  # Copia todos los datos recibidos
        "iat": int(now.timestamp()),
        "exp": int(expire.timestamp())
    }
    
    token = jwt.encode(payload, settings.jwt_secret, algorithm="HS256")
    return token