from pydantic_settings import BaseSettings

class Settings(BaseSettings):
    """Configuración del Broker Layer"""
    
    # Data Layer
    data_layer_url: str = "http://localhost:8080"
    data_layer_timeout: int = 30
    
    # Broker
    broker_host: str = "0.0.0.0"
    broker_port: int = 5000
    broker_env: str = "development"
    
    # API
    api_prefix: str = "/api"
    
    # IDs de roles (sincronizados con Data Layer)
    ROLE_ATHLETE_ID: int = 1
    ROLE_TRAINER_ID: int = 2
    ROLE_FIELD_MANAGER_ID: int = 3
    ROLE_SUPER_ADMIN_ID: int = 4

    # JWT
    jwt_secret: str = "tu_secreto_super_seguro_cambialo_en_produccion"   # en .env pones un valor real
    jwt_exp_minutes: int = 1440  # 24 horas

    class Config:
        env_file = ".env"
        case_sensitive = False

settings = Settings()
