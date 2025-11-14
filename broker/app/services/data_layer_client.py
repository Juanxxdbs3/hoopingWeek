import httpx
from typing import Dict, Any, Optional
from app.config.settings import settings

class DataLayerClient:
    """Cliente para comunicarse con Data Layer"""
    
    async def health_check(self) -> Dict[str, Any]:
        """Verificar si Data Layer está activo"""
        async with httpx.AsyncClient(timeout=5.0) as client:
            try:
                response = await client.get(f"{settings.data_layer_url}/health")
                response.raise_for_status()
                return response.json()
            except Exception as e:
                return {"status": "error", "message": str(e)}
    
    async def get(self, endpoint: str, params: Optional[Dict] = None) -> Dict[str, Any]:
        """GET request a Data Layer"""
        async with httpx.AsyncClient(timeout=30.0) as client:
            response = await client.get(
                f"{settings.data_layer_url}{endpoint}",
                params=params  # ← Ahora sí se pasa correctamente
            )
            response.raise_for_status()
            return response.json()
    
    async def post(self, endpoint: str, data: Dict[str, Any]) -> Dict[str, Any]:
        """POST request a Data Layer"""
        async with httpx.AsyncClient(timeout=30.0) as client:
            response = await client.post(
                f"{settings.data_layer_url}{endpoint}",
                json=data
            )
            response.raise_for_status()
            return response.json()