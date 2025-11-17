from fastapi import FastAPI
from app.routers import health, reservations, users, reservations_approvals
from app.config.settings import settings

app = FastAPI(
    title="Hooping Week Broker",
    version="1.0.0"
)

# Routers
app.include_router(health.router)
app.include_router(reservations.router)
app.include_router(users.router)
app.include_router(reservations_approvals.router)

@app.get("/")
async def root():
    return {
        "service": "broker",
        "status": "running",
        "message": "🚀 Broker activo",
        "data_layer": settings.data_layer_url
    }

@app.on_event("startup")
async def startup_event():
    print("=" * 60)
    print("✅ Broker iniciado correctamente")
    print(f"📡 Data Layer: {settings.data_layer_url}")
    print("=" * 60)
    print("📋 Rutas registradas:")
    for route in app.routes:
        if hasattr(route, "methods"):
            methods = ",".join(route.methods)
            print(f"  {methods:8} {route.path}")
    print("=" * 60)