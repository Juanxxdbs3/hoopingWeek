from typing import Dict, Any
from app.services.data_layer_client import DataLayerClient
from app.models.business_rules import BusinessRules
from app.models.schemas import UserRegisterRequest
import hashlib

class UserOrchestrator:
    """Orquestador de usuarios con validaciones de negocio"""
    
    def __init__(self):
        self.data_layer = DataLayerClient()
        self.rules = BusinessRules()
    
    async def register_user(self, request: UserRegisterRequest) -> Dict[str, Any]:
        """
        Registra un usuario aplicando validaciones de negocio
        
        Flujo:
        1. Validar edad
        2. Validar teléfono
        3. Verificar unicidad de ID (documento)
        4. Verificar unicidad de email
        5. Validar estatura (si es atleta)
        6. Crear usuario en Data Layer
        """
        errors = []
        
        # 1. Validar edad
        valid, msg = self.rules.validate_age(request.birth_date)
        if not valid:
            errors.append(msg)
        
        # 2. Validar teléfono
        valid, msg = self.rules.validate_phone(request.phone)
        if not valid:
            errors.append(msg)
        
        if errors:
            return {"ok": False, "errors": errors}
        
        # 3. Verificar unicidad de ID (documento)
        id_exists = await self._check_id_exists(request.id)
        if id_exists:
            errors.append(f"El documento {request.id} ya está registrado")
        
        # 4. Verificar unicidad de email
        email_exists = await self._check_email_exists(request.email)
        if email_exists:
            errors.append(f"El email {request.email} ya está registrado")
        
        if errors:
            return {"ok": False, "errors": errors}
        
        # 5. Preparar datos para Data Layer
        user_data = {
            "id": request.id,
            "first_name": request.first_name,
            "last_name": request.last_name,
            "email": request.email,
            "phone": request.phone,
            "password": request.password,  # Data Layer lo hasheará
            "role_id": request.role_id,
            "state_id": self.rules.DEFAULT_USER_STATE,  # 1 = active
            "athlete_state_id": self.rules.get_default_athlete_state(request.role_id),
            "height": request.height,
            "birth_date": request.birth_date.isoformat()
        }
        
        # 6. Crear en Data Layer
        try:
            result = await self.data_layer.post("/api/users", user_data)
        except Exception as e:
            return {
                "ok": False,
                "errors": [f"Error al crear usuario: {str(e)}"]
            }
        
        if not result.get("ok"):
            return result
        
        return {
            "ok": True,
            "user": result["user"],
            "validations": {
                "age_check": "passed",
                "phone_check": "passed",
                "email_unique": "passed",
                "id_unique": "passed",
                "role": self._get_role_name(request.role_id)
            },
            "errors": []
        }
    
    async def _check_id_exists(self, user_id: int) -> bool:
        """Verifica si el ID (documento) ya existe"""
        try:
            result = await self.data_layer.get(f"/api/users/{user_id}")
            return result.get("ok", False)  # Si retorna ok=true, el usuario existe
        except:
            return False  # Si falla (404), no existe
    
    async def _check_email_exists(self, email: str) -> bool:
        """Verifica si el email ya existe"""
        try:
            # Obtener todos los usuarios y buscar el email
            result = await self.data_layer.get("/api/users")
            
            if not result.get("ok"):
                return False
            
            users = result["users"]["data"]
            return any(u["email"].lower() == email.lower() for u in users)
        except:
            return False
    
    def _get_role_name(self, role_id: int) -> str:
        """Retorna el nombre del rol"""
        roles = {1: "athlete", 2: "trainer", 3: "field_manager", 4: "super_admin"}
        return roles.get(role_id, "unknown")