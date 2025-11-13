# Hooping Week - Data Layer

Capa de acceso a datos (Data Access Layer) del sistema de gestión de reservas deportivas. Proporciona una API REST para operaciones CRUD sobre la base de datos.

---

## 🛠️ Stack Tecnológico

- **PHP** 8.2+
- **Framework:** Slim 4.12
- **Base de datos:** MariaDB 10.4+
- **Servidor:** Apache 2.4 (XAMPP)
- **Gestión de dependencias:** Composer 2.x
- **Patrón:** Repository Pattern

---

## 📋 Requisitos

- XAMPP 8.2+ (incluye Apache + MariaDB + PHP)
- Composer 2.x
- Git (opcional)

---

## 🚀 Instalación

### 1. Instalar dependencias

```bash
cd C:\xampp\htdocs\hooping_week\data-layer
composer install
```

### 2. Configurar variables de entorno

Edita `.env` con tus credenciales de base de datos:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hooping_week
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
DB_CHARSET=utf8mb4
DB_POOL_SIZE=3
```

### 3. Crear base de datos

```bash
# Ejecutar en MySQL/MariaDB
mysql -u root -p

CREATE DATABASE hooping_week CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# Importar esquema
mysql -u root -p hooping_week < sql/schema.sql
```

### 4. Configurar Apache VirtualHost

**A. Editar `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:**

Agregar al final:

```apache
Listen 8080

<VirtualHost *:8080>
    ServerName data.hooping.local
    ServerAlias localhost
    DocumentRoot "C:/xampp/htdocs/hooping_week/data-layer/public"
    
    <Directory "C:/xampp/htdocs/hooping_week/data-layer/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ index.php [QSA,L]
    </Directory>
    
    ErrorLog "logs/hooping-data-error.log"
    CustomLog "logs/hooping-data-access.log" common
</VirtualHost>
```

**B. Editar `C:\Windows\System32\drivers\etc\hosts` (como Administrador):**

Agregar:

```
127.0.0.1    data.hooping.local
```

**C. Reiniciar Apache** desde XAMPP Control Panel.

### 5. Verificar instalación

```bash
# Abrir en navegador o con curl:
http://localhost:8080/health
```

**Respuesta esperada:**
```json
{
  "service": "data-layer",
  "status": "healthy",
  "timestamp": "2025-11-13T12:00:00+01:00",
  "db_pool_size": 3
}
```

---

## 🧪 Pruebas

### Endpoints básicos:

```bash
# Health check
GET http://localhost:8080/health

# Info del servicio
GET http://localhost:8080/

# Listar usuarios
GET http://localhost:8080/api/users

# Listar campos
GET http://localhost:8080/api/fields
```

---

## 🗂️ Estructura

```
data-layer/
├── public/
│   └── index.php          # Punto de entrada
├── src/
│   ├── config/            # Configuración
│   ├── controllers/       # Controladores
│   ├── database/          # Pool de conexiones
│   ├── repositories/      # Acceso a BD
│   └── routes/            # Definición de rutas
├── sql/
│   └── schema.sql         # Esquema de BD
├── vendor/                # Dependencias
├── .env                   # Variables de entorno
├── composer.json
└── README.md
```

---

## 🐛 Solución de problemas

### Error: "Call to undefined method"

**Causa:** Cambio de nombres de métodos.

**Solución:** Verificar que `index.php` use `BDConnection::init($config['db'])`.

### Error: "Connection refused"

**Solución:** 
1. Verificar que Apache escuche en puerto 8080 (ver XAMPP logs)
2. Reiniciar Apache

### Error: "Access denied for user"

**Solución:** Verificar credenciales en `.env`

---

## 📄 Licencia

MIT License

---

## 🔗 Documentación completa

Ver: `../README.md` (raíz del proyecto)