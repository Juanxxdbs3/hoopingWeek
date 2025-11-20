from fastapi import FastAPI
from app.routers import health, reservations, users, reservations_approvals, fields, manager_shifts, auth  # ← AGREGAR
from app.config.settings import settings
from fastapi.middleware.cors import CORSMiddleware # <--- IMPORTAR ESTO

app = FastAPI(
    title="Hooping Week Broker",
    version="1.0.0"
)

# --- AUTORIZAR FRONTEND ---
origins = [
    "http://localhost:5173",
    "http://127.0.0.1:5173",
]

app.add_middleware(
    CORSMiddleware,
    allow_origins=origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)
# --------------------

# Routers
app.include_router(auth.router)
app.include_router(health.router)
app.include_router(reservations.router)
app.include_router(users.router)
app.include_router(reservations_approvals.router)
app.include_router(fields.router)  # ← AGREGAR
app.include_router(manager_shifts.router)  # ← AGREGAR después de fields.router

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