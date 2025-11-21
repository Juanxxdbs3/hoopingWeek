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


# ========== SCHEMAS DE APROBACIONES ==========

class ApproveRequest(BaseModel):
    """Request para aprobar una reserva"""
    approver_id: Optional[int] = None # Opcional porque lo tomamos del token
    note: Optional[str] = Field(None, max_length=500)

class RejectRequest(BaseModel):
    """Request para rechazar una reserva"""
    approver_id: Optional[int] = None
    rejection_reason: str = Field(..., min_length=3, max_length=500)

class ReservationCancelRequest(BaseModel):
    """Request para cancelar una reserva"""
    reason: str = Field(..., min_length=3, max_length=500)

class SimpleResponse(BaseModel):
    """Respuesta simple con mensaje"""
    ok: bool
    message: str
    reservation: Optional[dict] = None


# ============================================
# MANAGER SHIFTS SCHEMAS
# ============================================

class ManagerShiftCreate(BaseModel):
    """Schema para crear turno de manager"""
    manager_id: int = Field(..., gt=0, description="ID del manager")
    field_id: int = Field(..., gt=0, description="ID del campo")
    day_of_week: int = Field(..., ge=0, le=6, description="Día (0=Domingo, 6=Sábado)")
    start_time: str = Field(..., pattern=r"^\d{2}:\d{2}:\d{2}$", description="HH:MM:SS")
    end_time: str = Field(..., pattern=r"^\d{2}:\d{2}:\d{2}$", description="HH:MM:SS")
    active: bool = Field(default=True, description="Si el turno está activo")
    note: Optional[str] = Field(None, max_length=500, description="Nota opcional")

    @field_validator('end_time')
    @classmethod
    def validate_times(cls, end_time: str, info) -> str:
        """Validar que end_time > start_time"""
        start_time = info.data.get('start_time')
        if start_time and end_time <= start_time:
            raise ValueError('end_time debe ser mayor que start_time')
        return end_time

    model_config = {
        "json_schema_extra": {
            "examples": [{
                "manager_id": 16,
                "field_id": 5,
                "day_of_week": 1,
                "start_time": "08:00:00",
                "end_time": "16:00:00",
                "active": True,
                "note": "Turno matutino lunes"
            }]
        }
    }


class ManagerShiftUpdate(BaseModel):
    """Schema para actualizar turno (todos los campos opcionales)"""
    manager_id: Optional[int] = Field(None, gt=0)
    field_id: Optional[int] = Field(None, gt=0)
    day_of_week: Optional[int] = Field(None, ge=0, le=6)
    start_time: Optional[str] = Field(None, pattern=r"^\d{2}:\d{2}:\d{2}$")
    end_time: Optional[str] = Field(None, pattern=r"^\d{2}:\d{2}:\d{2}$")
    active: Optional[bool] = None
    note: Optional[str] = Field(None, max_length=500)

    @model_validator(mode='after')
    def validate_times_if_both_present(self):
        """Si ambos horarios están presentes, validar que end > start"""
        if self.start_time and self.end_time:
            if self.end_time <= self.start_time:
                raise ValueError('end_time debe ser mayor que start_time')
        return self


class ManagerShiftResponse(BaseModel):
    """Schema de respuesta de turno"""
    id: int
    manager_id: int
    field_id: int
    day_of_week: int
    start_time: str
    end_time: str
    active: bool
    note: Optional[str] = None


# ========== TEAMS SCHEMAS ==========
class TeamCreate(BaseModel):
    name: str = Field(..., min_length=2, max_length=255)
    sport: str = Field(..., min_length=2, max_length=100)
    type: str = Field(..., min_length=2, max_length=100, example="club")
    trainer_id: int = Field(..., gt=0)
    locality: Optional[str] = Field(None, max_length=255)

class TeamUpdate(BaseModel):
    name: Optional[str] = Field(None, min_length=2, max_length=255)
    sport: Optional[str] = Field(None, min_length=2, max_length=100)
    type: Optional[str] = Field(None, min_length=2, max_length=100)
    trainer_id: Optional[int] = Field(None, gt=0)
    locality: Optional[str] = Field(None, max_length=255)

class TeamResponse(BaseModel):
    id: int
    name: str
    sport: str
    type: str
    trainer_id: int
    locality: Optional[str]
    created_at: str

class TeamMemberAdd(BaseModel):
    athlete_id: int = Field(..., gt=0, description="ID del atleta (debe existir)")
    join_date: Optional[date] = Field(None, description="Fecha de ingreso (default: hoy)")

class TeamMemberResponse(BaseModel):
    id: int
    team_id: int
    athlete_id: int
    join_date: date
    state: str
    first_name: str
    last_name: str
    email: str
    phone: Optional[str]

# ========== CHAMPIONSHIPS SCHEMAS ==========
class ChampionshipCreate(BaseModel):
    name: str = Field(..., min_length=3, max_length=255)
    organizer_id: int = Field(..., gt=0)
    sport: str = Field(..., min_length=2, max_length=100)
    start_date: date
    end_date: date
    status: Optional[str] = Field("planning", pattern="^(planning|active|finished|cancelled)$")

    @model_validator(mode='after')
    def validate_dates(self):
        if self.start_date >= self.end_date:
            raise ValueError('end_date debe ser posterior a start_date')
        return self

class ChampionshipUpdate(BaseModel):
    name: Optional[str] = Field(None, min_length=3, max_length=255)
    organizer_id: Optional[int] = Field(None, gt=0)
    sport: Optional[str] = Field(None, min_length=2, max_length=100)
    start_date: Optional[date] = None
    end_date: Optional[date] = None
    status: Optional[str] = Field(None, pattern="^(planning|active|finished|cancelled)$")

    @model_validator(mode='after')
    def validate_dates(self):
        if self.start_date and self.end_date:
            if self.start_date >= self.end_date:
                raise ValueError('end_date debe ser posterior a start_date')
        return self

class ChampionshipResponse(BaseModel):
    id: int
    name: str
    organizer_id: int
    sport: str
    start_date: date
    end_date: date
    status: str
    created_at: str

class ChampionshipTeamAdd(BaseModel):
    team_id: int = Field(..., gt=0)