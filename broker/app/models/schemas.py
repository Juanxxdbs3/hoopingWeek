from pydantic import BaseModel, Field, EmailStr, model_validator, field_validator
from datetime import datetime, date            # <-- datetime aquí
from typing import Optional, List             # <-- List aquí
import re


class ReservationParticipant(BaseModel):
    participant_id: int
    participant_type: str = Field(..., pattern="^(individual|team_member)$")
    team_id: Optional[int] = None

class ReservationCreateRequest(BaseModel):
    field_id: int
    applicant_id: int
    activity_type: str = Field(..., pattern="^(practice_individual|practice_group|match_friendly|match_championship)$")
    start_datetime: datetime
    end_datetime: datetime
    participants: List[ReservationParticipant] = []
    notes: Optional[str] = None

class ValidationResult(BaseModel):
    ds_check: str  # "passed" | "failed"
    dcc_check: str
    conflict_check: str
    priority: int

class ReservationCreateResponse(BaseModel):
    ok: bool
    reservation: Optional[dict] = None
    validations: Optional[ValidationResult] = None
    errors: List[str] = []

class UserRegisterRequest(BaseModel):
    """Request para registrar usuario"""
    id: int = Field(..., description="Número de documento (cédula)")
    first_name: str = Field(..., min_length=2, max_length=100)
    last_name: str = Field(..., min_length=2, max_length=100)
    email: EmailStr
    phone: str = Field(..., pattern=r"^\d{10}$", description="Teléfono de 10 dígitos")
    password: str = Field(..., min_length=8, description="Contraseña (mínimo 8 caracteres)")
    role_id: int = Field(..., ge=1, le=4, description="1=athlete, 2=trainer, 3=field_manager, 4=super_admin")
    height: Optional[float] = Field(None, ge=1.0, le=2.5, description="Estatura en metros (obligatorio para atletas)")
    birth_date: date = Field(..., description="Fecha de nacimiento (YYYY-MM-DD)")
    
    @model_validator(mode='after')
    def validate_height_for_athlete(self):
        """Si es atleta (role_id=1), height es obligatorio"""
        if self.role_id == 1 and self.height is None:
            raise ValueError("La estatura es obligatoria para atletas")
        return self

    @field_validator('phone')
    @classmethod
    def validate_phone_format(cls, v):
        if not v.isdigit() or len(v) != 10:
            raise ValueError("El teléfono debe tener exactamente 10 dígitos numéricos")
        return v

class UserRegisterResponse(BaseModel):
    ok: bool
    user: Optional[dict] = None
    validations: Optional[dict] = None
    errors: List[str] = []