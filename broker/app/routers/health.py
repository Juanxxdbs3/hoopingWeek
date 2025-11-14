from fastapi import APIRouter
from datetime import datetime
from app.services.data_layer_client import DataLayerClient

router = APIRouter()

@router.get("/health")
async def health_check():
    """Verifica Broker y Data Layer"""
    client = DataLayerClient()
    data_layer = await client.health_check()
    
    return {
        "broker": {
            "status": "healthy",
            "message": "✅ Broker funcionando"
        },
        "data_layer": data_layer,
        "timestamp": datetime.now().isoformat()
    }