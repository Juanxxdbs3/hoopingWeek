from datetime import datetime, time, date
from typing import Dict, Optional, List
import re

class BusinessRules:
    """Reglas de negocio simplificadas del sistema"""
    
    # DS: Disponibilidad de Escenarios
    DS_RULES = {
        "min_duration_hours": 0.5,
        "max_duration_hours": 4.0,
        "min_advance_hours": 1,  # ← Cambiado de 2 a 1
        "max_advance_days": 30
    }
    
    # DCC: Fechas especiales
    SPECIAL_DATES = {
        "2025-12-25": {"name": "Navidad", "blocked": True},
        "2025-01-01": {"name": "Año Nuevo", "blocked": True},
        "2025-12-24": {"name": "Nochebuena", "blocked": False, "close_early": "18:00"}
    }
    
    # Prioridades por tipo de actividad
    PRIORITY_BY_ACTIVITY = {
        "match_championship": 1,
        "match_friendly": 2,
        "practice_group": 3,
        "practice_individual": 4
    }
    
    # Ajuste de prioridad por rol
    PRIORITY_ADJUSTMENT_BY_ROLE = {
        "super_admin": -1,
        "field_manager": 0,  # ← No debería reservar, pero si pasa, sin ajuste
        "trainer": -1,
        "athlete": 0
    }
    
    # ========== REGLAS DE USUARIOS ==========
    
    USER_RULES = {
        "min_age": 10,
        "max_age": 100,
        "phone_pattern": r"^\d{10}$",
        "password_min_length": 8
    }
    
    DEFAULT_USER_STATE = 1
    DEFAULT_ATHLETE_STATE = None
    
    # Sistema de aprobaciones
    APPROVAL_RULES = {
        "practice_individual": {"auto_approve": True},
        "practice_group": {"auto_approve": True},
        "match_friendly": {"requires": ["trainer_approval"], "auto_approve": False},
        "match_championship": {"requires": ["field_manager_approval"], "auto_approve": False}
    }
    
    @staticmethod
    def validate_duration(start: datetime, end: datetime) -> tuple[bool, str]:
        """Valida duración de la reserva"""
        duration_hours = (end - start).total_seconds() / 3600
        
        if duration_hours < BusinessRules.DS_RULES["min_duration_hours"]:
            return False, f"Duración mínima: {BusinessRules.DS_RULES['min_duration_hours']} horas"
        
        if duration_hours > BusinessRules.DS_RULES["max_duration_hours"]:
            return False, f"Duración máxima: {BusinessRules.DS_RULES['max_duration_hours']} horas"
        
        return True, ""
    
    @staticmethod
    def validate_advance_time(start: datetime) -> tuple[bool, str]:
        """Valida anticipación de la reserva"""
        now = datetime.now()
        advance_hours = (start - now).total_seconds() / 3600
        advance_days = (start - now).days
        
        if advance_hours < BusinessRules.DS_RULES["min_advance_hours"]:
            return False, f"Debe reservar con al menos {BusinessRules.DS_RULES['min_advance_hours']} hora(s) de anticipación"
        
        if advance_days > BusinessRules.DS_RULES["max_advance_days"]:
            return False, f"No se puede reservar con más de {BusinessRules.DS_RULES['max_advance_days']} días de anticipación"
        
        return True, ""
    
    @staticmethod
    def is_date_blocked(date: datetime) -> tuple[bool, str]:
        """Verifica si una fecha está bloqueada (DCC)"""
        date_str = date.strftime("%Y-%m-%d")
        
        if date_str in BusinessRules.SPECIAL_DATES:
            special = BusinessRules.SPECIAL_DATES[date_str]
            if special.get("blocked"):
                return True, f"Fecha bloqueada: {special['name']}"
        
        return False, ""
    
    @staticmethod
    def calculate_priority(activity_type: str, role_name: str) -> int:
        """Calcula prioridad de una reserva"""
        base_priority = BusinessRules.PRIORITY_BY_ACTIVITY.get(activity_type, 4)
        adjustment = BusinessRules.PRIORITY_ADJUSTMENT_BY_ROLE.get(role_name, 0)
        
        final_priority = base_priority + adjustment
        return max(1, min(4, final_priority))
    
    @staticmethod
    def validate_age(birth_date: date) -> tuple[bool, str]:
        """Valida que la edad esté en el rango permitido"""
        today = date.today()
        age = today.year - birth_date.year - ((today.month, today.day) < (birth_date.month, birth_date.day))
        
        if age < BusinessRules.USER_RULES["min_age"]:
            return False, f"Edad mínima: {BusinessRules.USER_RULES['min_age']} años"
        
        if age > BusinessRules.USER_RULES["max_age"]:
            return False, f"Edad máxima: {BusinessRules.USER_RULES['max_age']} años"
        
        return True, ""
    
    @staticmethod
    def validate_phone(phone: str) -> tuple[bool, str]:
        """Valida formato de teléfono colombiano"""
        if not re.match(BusinessRules.USER_RULES["phone_pattern"], phone):
            return False, "El teléfono debe tener 10 dígitos"
        
        return True, ""
    
    @staticmethod
    def get_default_athlete_state(role_id: int) -> Optional[int]:
        """Retorna el athlete_state_id por defecto según el rol"""
        return None  # Siempre NULL por defecto
    
    @staticmethod
    def validate_activity_for_role(
        activity_type: str, 
        role_id: int, 
        participants: List = []
    ) -> tuple[bool, str]:
        """
        Valida que una actividad sea permitida para un rol
        
        Reglas especiales:
        - Field manager (3) NO puede reservar nunca
        - Trainer (2) puede crear practice_individual SOLO si tiene participantes atletas
        - Athlete (1) puede crear cualquier actividad
        - Super admin (4) puede crear cualquier actividad
        """
        # Field manager NO puede reservar
        if role_id == 3:
            return False, "Los administradores de cancha no pueden crear reservas propias"
        
        # Trainer con practice_individual DEBE tener participantes
        if role_id == 2 and activity_type == "practice_individual":
            if not participants or len(participants) == 0:
                return False, "Los entrenadores deben especificar al menos un atleta participante para prácticas individuales"
            
            # TODO: Validar que los participantes sean atletas (role_id=1)
            # Esto lo haremos en el orchestrator para no hacer llamadas a BD aquí
        
        # Athlete y Super admin pueden todo
        if role_id in [1, 4]:
            return True, ""
        
        # Trainer puede crear práctica grupal y partidos
        if role_id == 2 and activity_type in ["practice_group", "match_friendly", "match_championship"]:
            return True, ""
        
        return False, f"El rol no permite crear reservas de tipo '{activity_type}'"
    
    @staticmethod
    def get_initial_status(activity_type: str) -> str:
        """Determina el estado inicial de una reserva según el tipo"""
        rule = BusinessRules.APPROVAL_RULES.get(activity_type, {})
        
        if rule.get("auto_approve", False):
            return "approved"
        
        return "pending"