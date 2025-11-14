# Hooping Week - Broker Layer

Capa de lógica de negocio (Business Logic Layer) del sistema de gestión de reservas deportivas. Orquesta las operaciones complejas y valida las reglas de negocio (DS/DCC) antes de interactuar con el Data Layer.

---

## 🏗️ Arquitectura

```
Frontend (React/Vue) 
    ↓ HTTP
Broker Layer (Python FastAPI) - Puerto 5000
    ↓ HTTP
Data Layer (PHP Slim) - Puerto 8080
    ↓ SQL
MariaDB
```

**Broker se encarga de:**
- ✅ Lógica de negocio compleja (validaciones DS/DCC)
- ✅ Orquestación de servicios
- ✅ Cálculo de rankings y estadísticas avanzadas
- ✅ Recomendaciones inteligentes
- ✅ Flujos de trabajo complejos
- ✅ Agregación de datos de múltiples endpoints del Data Layer

---

## 🛠️ Stack Tecnológico

- **Python**: 3.10+
- **Framework**: FastAPI 0.121+
- **HTTP Client**: httpx (async)
- **Validación**: Pydantic v2
- **Servidor**: Uvicorn
- **Documentación**: OpenAPI/Swagger (automática)

---

## 📦 Módulos Implementados

### 1. **Health Check** (`/health`)
- Verifica estado del Broker
- Comprueba conectividad con Data Layer
- Muestra métricas del sistema

### 2. **Reservation Orchestrator** (`/api/reservations/*`)
- Validación de disponibilidad de escenarios (DS)
- Validación de calendario y fechas especiales (DCC)
- Detección de conflictos de horario
- Cálculo automático de prioridades
- Flujo de aprobación/rechazo

### 3. **Rankings** (`/api/rankings/*`)
- Ranking de equipos por actividad
- Ranking de usuarios más activos
- Ranking de campos más utilizados
- Estadísticas de rendimiento

### 4. **Recommendations** (`/api/recommendations/*`)
- Sugerencias de horarios óptimos
- Recomendación de campos disponibles
- Predicciones de uso

### 5. **Analytics** (`/api/analytics/*`)
- Reportes complejos personalizados
- Tendencias de uso
- Análisis de patrones de reserva

---

## 🚀 Instalación

### Prerrequisitos:

- Python 3.10+
- Data Layer funcionando en puerto 8080
- MariaDB activa

### Pasos:

#### 1. Crear entorno virtual

```bash
cd C:\xampp\htdocs\hooping_week\broker
python -m venv venv

# Activar (Windows)
venv\Scripts\activate

# Activar (Linux/Mac)
source venv/bin/activate
```

#### 2. Instalar dependencias

```bash
pip install -r requirements.txt
```

#### 3. Configurar variables de entorno

Edita `.env`:

```env
# Data Layer
DATA_LAYER_URL=http://localhost:8080

# Broker
BROKER_HOST=0.0.0.0
BROKER_PORT=5000
BROKER_ENV=development

# Timeouts (segundos)
DATA_LAYER_TIMEOUT=30
```

#### 4. Ejecutar servidor

```bash
uvicorn app.main:app --reload --port 5000
```

#### 5. Verificar instalación

```bash
# Health check
curl http://localhost:5000/health

# Documentación interactiva
# Abrir en navegador:
http://localhost:5000/docs
```

---

## 📡 Endpoints Principales

### Health Check

```
GET /health
```

**Respuesta:**
```json
{
  "broker": {
    "service": "broker",
    "status": "healthy"
  },
  "data_layer": {
    "service": "data-layer",
    "status": "healthy",
    "timestamp": "2025-11-13T22:30:00+01:00"
  },
  "timestamp": "2025-11-13T22:30:01+01:00"
}
```

### Crear Reserva con Validación Completa

```
POST /api/reservations/create-with-validation
```

**Body:**
```json
{
  "field_id": 5,
  "applicant_id": 1,
  "activity_type": "practice_group",
  "start_datetime": "2025-11-20T10:00:00",
  "end_datetime": "2025-11-20T12:00:00",
  "participants": [
    {"participant_id": 15, "participant_type": "athlete"}
  ]
}
```

**Respuesta:**
```json
{
  "ok": true,
  "reservation": {
    "id": 42,
    "status": "approved",
    "priority": 2
  },
  "validations": {
    "ds_check": "passed",
    "dcc_check": "passed",
    "conflict_check": "passed"
  }
}
```

### Rankings de Equipos

```
GET /api/rankings/teams?limit=10
```

### Recomendaciones de Horarios

```
GET /api/recommendations/best-times?field_id=5&date=2025-11-20
```

---

## 🗂️ Estructura del Proyecto

```
broker/
├── app/
│   ├── main.py                 # Punto de entrada FastAPI
│   │
│   ├── config/
│   │   └── settings.py         # Configuración
│   │
│   ├── routers/
│   │   ├── health.py           # Health check
│   │   ├── reservations.py     # Orquestación de reservas
│   │   ├── rankings.py         # Rankings
│   │   └── recommendations.py  # Recomendaciones
│   │
│   ├── services/
│   │   ├── data_layer_client.py          # Cliente HTTP
│   │   ├── reservation_orchestrator.py   # Lógica de reservas
│   │   ├── ranking_calculator.py         # Cálculo de rankings
│   │   └── validator.py                  # Validaciones DS/DCC
│   │
│   ├── models/
│   │   ├── schemas.py          # Pydantic models
│   │   └── business_rules.py   # Reglas de negocio
│   │
│   └── utils/
│       └── helpers.py          # Funciones auxiliares
│
├── tests/
├── .env
├── requirements.txt
└── README.md
```

---

## 🧪 Testing

### Ejecutar tests

```bash
pytest tests/ -v
```

### Coverage

```bash
pytest --cov=app tests/
```

---

## 📖 Documentación Interactiva

FastAPI genera documentación automática:

- **Swagger UI:** http://localhost:5000/docs
- **ReDoc:** http://localhost:5000/redoc
- **OpenAPI JSON:** http://localhost:5000/openapi.json

---

## 🔒 Seguridad

### Implementado:
- ✅ Validación de datos con Pydantic
- ✅ CORS configurado
- ✅ Timeouts en requests HTTP
- ✅ Manejo de errores centralizado

### Pendiente:
- ⚠️ Autenticación JWT
- ⚠️ Rate limiting
- ⚠️ HTTPS

---

## 🐛 Troubleshooting

### Error: "Connection refused to Data Layer"

**Causa:** Data Layer no está corriendo o puerto incorrecto.

**Solución:**
```bash
# Verificar Data Layer
curl http://localhost:8080/health
```

### Error: "ModuleNotFoundError"

**Solución:**
```bash
pip install -r requirements.txt
```

### Error: "Port 5000 already in use"

**Solución:**
```bash
# Cambiar puerto
uvicorn app.main:app --reload --port 5001
```

---

## 🔗 Enlaces

- **Data Layer:** `../data-layer/README.md`
- **Documentación API:** http://localhost:5000/docs
- **Repositorio:** https://github.com/tu-usuario/hooping_week

---

## 📊 Métricas

- **Endpoints implementados:** 15+
- **Validaciones de negocio:** DS + DCC
- **Latencia promedio:** < 200ms
- **Async HTTP client:** httpx

---

**Última actualización:** 13 de noviembre de 2025