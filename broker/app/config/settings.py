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
    
    class Config:
        env_file = ".env"
        case_sensitive = False

settings = Settings()