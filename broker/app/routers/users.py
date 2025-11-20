from fastapi import APIRouter, HTTPException, status
from app.services.user_orchestrator import UserOrchestrator
from app.services.data_layer_client import DataLayerClient
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

@router.get("/{user_id}")
async def get_user_by_id(user_id: int):
    """
    Obtener usuario por ID (Proxy al Data Layer)
    Útil para el login simplificado por ID.
    """
    client = DataLayerClient()
    
    try:
        # Hacemos la petición al Data Layer
        response = await client.get(f"/api/users/{user_id}")
        
        # Si el Data Layer dice que no está ok (ej. 404 o error lógico)
        if not response.get("ok"):
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail="Usuario no encontrado"
            )
            
        return response

    except HTTPException:
        raise
    except Exception as e:
        # Si falla la conexión con el Data Layer
        print(f"Error proxy users: {e}")
        raise HTTPException(
            status_code=status.HTTP_502_BAD_GATEWAY,
            detail="Error de comunicación con el servicio de datos"
        )