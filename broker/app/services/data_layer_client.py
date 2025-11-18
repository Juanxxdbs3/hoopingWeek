import httpx
from typing import Dict, Any, Optional
from app.config.settings import settings

class DataLayerClient:
    """Cliente para comunicarse con Data Layer"""
    
    def __init__(self):
        self.base_url = settings.data_layer_url
        self.timeout = 30.0
    
    async def health_check(self) -> Dict[str, Any]:
        """Verificar si Data Layer está activo"""
        async with httpx.AsyncClient(timeout=5.0) as client:
            try:
                response = await client.get(f"{self.base_url}/health")
                response.raise_for_status()
                return response.json()
            except Exception as e:
                return {"status": "error", "message": str(e)}
    
    async def get(self, endpoint: str, params: Optional[Dict] = None) -> Dict[str, Any]:
        """GET request a Data Layer"""
        async with httpx.AsyncClient(timeout=self.timeout) as client:
            url = f"{self.base_url}{endpoint}"
            try:
                response = await client.get(url, params=params)
                response.raise_for_status()
                return response.json()
            except httpx.HTTPStatusError as e:
                print(f"❌ HTTP Error {e.response.status_code} en GET {endpoint}")
                print(f"   Response: {e.response.text}")
                raise
            except Exception as e:
                print(f"❌ Error en GET {endpoint}: {e}")
                raise
    
    async def post(self, endpoint: str, data: Dict[str, Any]) -> Dict[str, Any]:
        """POST request a Data Layer"""
        async with httpx.AsyncClient(timeout=self.timeout) as client:
            url = f"{self.base_url}{endpoint}"
            try:
                response = await client.post(url, json=data)
                response.raise_for_status()
                return response.json()
            except httpx.HTTPStatusError as e:
                print(f"❌ HTTP Error {e.response.status_code} en POST {endpoint}")
                print(f"   Response: {e.response.text}")
                raise
            except Exception as e:
                print(f"❌ Error en POST {endpoint}: {e}")
                raise
    
    async def patch(self, endpoint: str, data: Dict[str, Any]) -> Dict[str, Any]:
        """PATCH request a Data Layer"""
        async with httpx.AsyncClient(timeout=self.timeout) as client:
            url = f"{self.base_url}{endpoint}"
            try:
                response = await client.patch(url, json=data)
                response.raise_for_status()
                return response.json()
            except httpx.HTTPStatusError as e:
                print(f"❌ HTTP Error {e.response.status_code} en PATCH {endpoint}")
                print(f"   Response: {e.response.text}")
                raise
            except Exception as e:
                print(f"❌ Error en PATCH {endpoint}: {e}")
                raise
    
    async def put(self, endpoint: str, data: Dict[str, Any] = None) -> Dict[str, Any]:
        """PUT request a Data Layer"""
        async with httpx.AsyncClient(timeout=self.timeout) as client:
            url = f"{self.base_url}{endpoint}"
            try:
                response = await client.put(url, json=data)
                response.raise_for_status()
                return response.json()
            except httpx.HTTPStatusError as e:
                print(f"❌ HTTP Error {e.response.status_code} en PUT {endpoint}")
                print(f"   Response: {e.response.text}")
                raise
            except Exception as e:
                print(f"❌ Error en PUT {endpoint}: {e}")
                raise
    
    async def delete(self, endpoint: str) -> Dict[str, Any]:
        """DELETE request a Data Layer"""
        async with httpx.AsyncClient(timeout=self.timeout) as client:
            url = f"{self.base_url}{endpoint}"
            try:
                response = await client.delete(url)
                response.raise_for_status()
                return response.json()
            except httpx.HTTPStatusError as e:
                print(f"❌ HTTP Error {e.response.status_code} en DELETE {endpoint}")
                print(f"   Response: {e.response.text}")
                raise
            except Exception as e:
                print(f"❌ Error en DELETE {endpoint}: {e}")
                raise