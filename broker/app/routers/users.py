from fastapi import APIRouter, HTTPException, Depends, Query, status
from typing import Dict, Any, Optional
from app.services.data_layer_client import DataLayerClient
from app.core.deps import get_current_active_user, require_super_admin
from app.models.schemas import UserRegisterRequest

router = APIRouter(prefix="/api/users", tags=["users"])


@router.get("")
async def list_users(
    email: Optional[str] = None,
    role_id: Optional[int] = None,
    state_id: Optional[int] = None,
    limit: int = Query(100, le=500),
    offset: int = Query(0, ge=0),
    current_user: Dict[str, Any] = Depends(get_current_active_user)
):
    """
    Lista usuarios con filtros opcionales
    
    **Permisos:** Solo SuperAdmin puede listar todos los usuarios
    """
    if current_user.get("role_id") != 4:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="No tienes permisos para listar usuarios"
        )
    
    client = DataLayerClient()
    params = {"limit": limit, "offset": offset}
    
    if email:
        params["email"] = email
    if role_id is not None:
        params["role_id"] = role_id
    if state_id is not None:
        params["state_id"] = state_id
    
    try:
        result = await client.get("/api/users", params)
        return result
    except Exception as e:
        print(f"❌ Error listando usuarios: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Error al obtener listado de usuarios"
        )


@router.get("/{user_id}")
async def get_user(
    user_id: int,
    current_user: Dict[str, Any] = Depends(get_current_active_user)
):
    """
    Obtener información de un usuario específico
    
    **Permisos:** 
    - Usuarios pueden ver su propia información
    - SuperAdmin puede ver cualquier usuario
    """
    if current_user.get("id") != user_id and current_user.get("role_id") != 4:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="No tienes permisos para ver este usuario"
        )
    
    client = DataLayerClient()
    
    try:
        result = await client.get(f"/api/users/{user_id}")
        return result
    except Exception as e:
        if "404" in str(e):
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail=f"Usuario {user_id} no encontrado"
            )
        print(f"❌ Error obteniendo usuario {user_id}: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Error al obtener usuario"
        )


@router.post("/register")
async def register_user(
    user_data: UserRegisterRequest
    # ❌ QUITAR: current_user: Dict[str, Any] = Depends(require_super_admin)
):
    """
    Registrar un nuevo usuario (endpoint PÚBLICO)
    
    **Permisos:** Sin autenticación requerida (cualquiera puede registrarse)
    """
    client = DataLayerClient()
    
    try:
        # ✅ CONVERTIR A DICT Y SERIALIZAR FECHA
        payload = user_data.model_dump()
        
        # Convertir fecha a string si existe
        if payload.get('birth_date'):
            payload['birth_date'] = str(payload['birth_date'])  # Convierte date a "YYYY-MM-DD"
        
        print(f"📤 Registrando usuario: {payload}")
        
        result = await client.post("/api/users", payload)
        return result
    except Exception as e:
        print(f"❌ Error registrando usuario: {e}")
        if "409" in str(e) or "duplicate" in str(e).lower():
            raise HTTPException(
                status_code=status.HTTP_409_CONFLICT,
                detail="El email o ID ya está registrado"
            )
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Error al registrar usuario"
        )


@router.put("/{user_id}")
async def update_user(
    user_id: int,
    user_data: Dict[str, Any],
    current_user: Dict[str, Any] = Depends(get_current_active_user)
):
    """
    Actualizar información de un usuario
    
    **Permisos:**
    - Usuarios pueden actualizar su propia información (excepto role_id)
    - SuperAdmin puede actualizar cualquier usuario
    """
    is_super_admin = current_user.get("role_id") == 4
    
    if current_user.get("id") != user_id and not is_super_admin:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="No tienes permisos para modificar este usuario"
        )
    
    if not is_super_admin and "role_id" in user_data:
        del user_data["role_id"]
    
    # ✅ Convertir fecha si existe
    if user_data.get('birth_date') and not isinstance(user_data['birth_date'], str):
        user_data['birth_date'] = str(user_data['birth_date'])
    
    client = DataLayerClient()
    
    try:
        result = await client.put(f"/api/users/{user_id}", user_data)
        return result
    except Exception as e:
        if "404" in str(e):
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail=f"Usuario {user_id} no encontrado"
            )
        print(f"❌ Error actualizando usuario {user_id}: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Error al actualizar usuario"
        )


@router.delete("/{user_id}")
async def delete_user(
    user_id: int,
    current_user: Dict[str, Any] = Depends(require_super_admin)
):
    """
    Eliminar (soft delete) un usuario
    
    **Permisos:** Solo SuperAdmin
    """
    if current_user.get("id") == user_id:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="No puedes eliminarte a ti mismo"
        )
    
    client = DataLayerClient()
    
    try:
        result = await client.delete(f"/api/users/{user_id}")
        return result
    except Exception as e:
        if "404" in str(e):
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail=f"Usuario {user_id} no encontrado"
            )
        print(f"❌ Error eliminando usuario {user_id}: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Error al eliminar usuario"
        )