from fastapi import APIRouter, HTTPException
from app.services.user_orchestrator import UserOrchestrator
from app.models.schemas import UserRegisterRequest, UserRegisterResponse

router = APIRouter(prefix="/api/users", tags=["users"])

@router.post("/register", response_model=UserRegisterResponse)
async def register_user(request: UserRegisterRequest):
    """
    Registra un nuevo usuario con validaciones de negocio
    
    Validaciones aplicadas:
    - Edad mínima: 10 años
    - Teléfono válido (10 dígitos)
    - ID (documento) único
    - Email único
    - Estatura obligatoria para atletas (role_id=1)
    
    Valores por defecto:
    - state_id: 1 (active)
    - athlete_state_id: 4 (in_championship) solo si es atleta
    """
    orchestrator = UserOrchestrator()
    result = await orchestrator.register_user(request)
    
    if not result.get("ok"):
        raise HTTPException(status_code=400, detail=result)
    
    return result