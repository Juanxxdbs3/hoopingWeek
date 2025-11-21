# broker/app/services/match_orchestrator.py
from app.services.data_layer_client import DataLayerClient

class MatchOrchestrator:
    def __init__(self):
        self.client = DataLayerClient()

    async def create(self, payload: dict):
        # Puedes agregar reglas de negocio centrales aquí en el futuro.
        return await self.client.post("/api/matches", payload)

    async def get(self, match_id: int):
        return await self.client.get(f"/api/matches/{match_id}")

    async def list(self, params: dict = None):
        return await self.client.get("/api/matches", params or {})

    async def by_reservation(self, reservation_id: int):
        return await self.client.get(f"/api/matches/by-reservation/{reservation_id}")

    async def delete(self, match_id: int):
        return await self.client.delete(f"/api/matches/{match_id}")
