from fastapi import APIRouter, HTTPException, Query, status
from typing import Optional
from app.models.schemas import ManagerShiftCreate, ManagerShiftUpdate
from app.services.data_layer_client import DataLayerClient

router = APIRouter(prefix="/api/manager-shifts", tags=["manager-shifts"])

@router.post("", status_code=status.HTTP_201_CREATED)
async def create_manager_shift(shift: ManagerShiftCreate):
    """
    Crear turno de manager
    
    **Validaciones:**
    - Manager debe existir y tener role_id = 3 (field_manager)
    - Campo debe existir
    - Horarios válidos (start < end)
    - No solapamiento con otros turnos del mismo manager en el mismo día
    """
    client = DataLayerClient()
    
    # 1. Validar que el manager existe y tiene rol correcto
    try:
        user_response = await client.get(f"/api/users/{shift.manager_id}")
        if not user_response.get("ok"):
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail=f"Usuario {shift.manager_id} no encontrado"
            )
        
        user = user_response.get("user", {})
        if user.get("role_id") != 3:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail=f"Usuario {shift.manager_id} no tiene rol de manager (role_id debe ser 3)"
            )
    except HTTPException:
        raise
    except Exception as e:
        if "404" in str(e):
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail=f"Usuario {shift.manager_id} no encontrado"
            )
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Error al validar usuario"
        )
    
    # 2. Validar que el campo existe
    try:
        field_response = await client.get(f"/api/fields/{shift.field_id}")
        if not field_response.get("ok"):
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail=f"Campo {shift.field_id} no encontrado"
            )
    except HTTPException:
        raise
    except Exception as e:
        if "404" in str(e):
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail=f"Campo {shift.field_id} no encontrado"
            )
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Error al validar campo"
        )
    
    # 3. Verificar solapamiento con otros turnos del mismo manager en el mismo día
    existing_shifts_response = await client.get(
        "/api/manager-shifts",
        {"manager_id": shift.manager_id, "day_of_week": shift.day_of_week}
    )
    
    if existing_shifts_response.get("ok"):
        existing_shifts = existing_shifts_response.get("manager_shifts", {}).get("data", [])
        
        for existing in existing_shifts:
            # Verificar solapamiento de horarios
            if (shift.start_time < existing["end_time"] and 
                shift.end_time > existing["start_time"]):
                raise HTTPException(
                    status_code=status.HTTP_409_CONFLICT,
                    detail=f"El turno solapa con turno existente ID {existing['id']} "
                           f"({existing['start_time']}-{existing['end_time']}) en campo {existing['field_id']}"
                )
    
    # 4. Crear el turno en Data Layer
    try:
        response = await client.post("/api/manager-shifts", shift.model_dump())
        
        if not response.get("ok"):
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail=response.get("error", "Error al crear turno")
            )
        
        return response
    
    except HTTPException:
        raise
    except Exception as e:
        print(f"❌ Error al crear turno: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Error interno al crear turno"
        )


@router.get("")
async def list_manager_shifts(
    manager_id: Optional[int] = Query(None, description="Filtrar por manager"),
    field_id: Optional[int] = Query(None, description="Filtrar por campo"),
    day_of_week: Optional[int] = Query(None, ge=0, le=6, description="Filtrar por día"),
    active: Optional[bool] = Query(None, description="Filtrar por activos"),
    limit: int = Query(100, ge=1, le=500),
    offset: int = Query(0, ge=0)
):
    """
    Listar turnos de managers con filtros opcionales
    """
    client = DataLayerClient()
    
    params = {"limit": limit, "offset": offset}
    if manager_id is not None:
        params["manager_id"] = manager_id
    if field_id is not None:
        params["field_id"] = field_id
    if day_of_week is not None:
        params["day_of_week"] = day_of_week
    if active is not None:
        params["active"] = 1 if active else 0
    
    try:
        response = await client.get("/api/manager-shifts", params)
        return response
    except Exception as e:
        print(f"❌ Error al listar turnos: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Error al obtener turnos"
        )


@router.get("/{shift_id}")
async def get_manager_shift(shift_id: int):
    """Obtener turno por ID"""
    client = DataLayerClient()
    
    try:
        response = await client.get(f"/api/manager-shifts/{shift_id}")
        
        if not response.get("ok"):
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail="Turno no encontrado"
            )
        
        return response
    except HTTPException:
        raise
    except Exception as e:
        if "404" in str(e):
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail="Turno no encontrado"
            )
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Error al obtener turno"
        )


@router.put("/{shift_id}")
async def update_manager_shift(shift_id: int, shift: ManagerShiftUpdate):
    """
    Actualizar turno existente
    
    **Validaciones:**
    - Si cambia manager_id, validar que tenga role_id = 3
    - Si cambia field_id, validar que exista
    - Validar solapamientos si cambian horarios/día
    """
    client = DataLayerClient()
    
    # 1. Verificar que el turno existe
    try:
        existing_response = await client.get(f"/api/manager-shifts/{shift_id}")
        if not existing_response.get("ok"):
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail="Turno no encontrado"
            )
        existing_shift = existing_response.get("manager_shift", {})
    except HTTPException:
        raise
    except Exception as e:
        if "404" in str(e):
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail="Turno no encontrado"
            )
        raise
    
    # 2. Validar manager si cambia
    if shift.manager_id is not None and shift.manager_id != existing_shift.get("manager_id"):
        try:
            user_response = await client.get(f"/api/users/{shift.manager_id}")
            if not user_response.get("ok"):
                raise HTTPException(
                    status_code=status.HTTP_404_NOT_FOUND,
                    detail=f"Usuario {shift.manager_id} no encontrado"
                )
            
            user = user_response.get("user", {})
            if user.get("role_id") != 3:
                raise HTTPException(
                    status_code=status.HTTP_400_BAD_REQUEST,
                    detail=f"Usuario {shift.manager_id} no tiene rol de manager"
                )
        except HTTPException:
            raise
        except Exception:
            pass
    
    # 3. Validar campo si cambia
    if shift.field_id is not None and shift.field_id != existing_shift.get("field_id"):
        try:
            field_response = await client.get(f"/api/fields/{shift.field_id}")
            if not field_response.get("ok"):
                raise HTTPException(
                    status_code=status.HTTP_404_NOT_FOUND,
                    detail=f"Campo {shift.field_id} no encontrado"
                )
        except HTTPException:
            raise
        except Exception:
            pass
    
    # 4. Actualizar en Data Layer
    try:
        # Filtrar solo campos no None
        update_data = {k: v for k, v in shift.model_dump().items() if v is not None}
        
        response = await client.put(f"/api/manager-shifts/{shift_id}", update_data)
        
        if not response.get("ok"):
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail=response.get("error", "Error al actualizar turno")
            )
        
        return response
    except HTTPException:
        raise
    except Exception as e:
        print(f"❌ Error al actualizar turno: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Error interno al actualizar turno"
        )


@router.delete("/{shift_id}")
async def delete_manager_shift(shift_id: int):
    """Eliminar turno"""
    client = DataLayerClient()
    
    try:
        response = await client.delete(f"/api/manager-shifts/{shift_id}")
        
        if not response.get("ok"):
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail="Turno no encontrado"
            )
        
        return {"ok": True, "message": "Turno eliminado correctamente"}
    except HTTPException:
        raise
    except Exception as e:
        if "404" in str(e):
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail="Turno no encontrado"
            )
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Error al eliminar turno"
        )