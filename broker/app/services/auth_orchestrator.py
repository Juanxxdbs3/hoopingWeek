# broker/app/services/auth_orchestrator.py
from typing import Dict, Any
from app.services.data_layer_client import DataLayerClient
import httpx

class AuthOrchestrator:
    """Orquesta autenticación delegando la verificación al Data Layer"""
    def __init__(self):
        self.data_layer = DataLayerClient()

    async def authenticate(self, identifier: str, password: str) -> Dict[str, Any]:
        """
        Llama a Data Layer /api/users/authenticate con {identifier,password}.
        Retorna el JSON que venga de Data Layer (normalmente {"ok": True/False, ...}).
        Maneja HTTPStatusError para poder devolver el JSON de error si existe.
        """
        payload = {"identifier": identifier, "password": password}
        try:
            result = await self.data_layer.post("/api/users/authenticate", payload)
            return result
        except httpx.HTTPStatusError as e:
            # Intentar extraer JSON si el Data Layer lo devolvió
            try:
                return e.response.json()
            except Exception:
                # Si no había JSON, re-lanzar para que el router lo capture como 502
                raise
        except Exception:
            # Re-lanzar (será capturado por el router)
            raise
