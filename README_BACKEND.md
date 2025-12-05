# 🏪 Sistema de Stock y Ventas - Lanería Mariano Díaz
## Backend API - Laravel

### 📋 Descripción
API RESTful para el sistema de gestión de inventario, ventas y clientes de la Lanería Mariano Díaz.

---

## 🚀 Tecnologías

- **Framework:** Laravel 10+
- **Base de Datos:** SQL Server
- **Autenticación:** Laravel Sanctum
- **PHP:** 8.1+

---

## 📦 Instalación

### 1. Clonar el repositorio
```bash
git clone <url-repositorio>
cd laneria-mariano-backend
```

### 2. Instalar dependencias
```bash
composer install
```

### 3. Configurar variables de entorno
```bash
cp .env.example .env
php artisan key:generate
```

Edita `.env` con tus credenciales de SQL Server:
```env
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=laneria_mariano_db
DB_USERNAME=sa
DB_PASSWORD=tu_contraseña
```

### 4. Ejecutar migraciones
```bash
php artisan migrate
```

### 5. Cargar datos de prueba (opcional)
```bash
php artisan db:seed
```

### 6. Iniciar servidor
```bash
php artisan serve
```

La API estará disponible en: `http://127.0.0.1:8000/api/v1`

---

## 🔐 Autenticación

### Login
**POST** `/api/v1/auth/login`
```json
{
  "email": "admin@laneriamariano.com",
  "password": "admin123"
}
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "user": {...},
    "token": "1|xxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

### Usar el token
Agrega el header en todas las peticiones protegidas:
```
Authorization: Bearer {tu_token_aqui}
```

---

## 👥 Roles de Usuario

- **administrador**: Acceso total al sistema
- **vendedor**: Puede gestionar productos, ventas, clientes
- **cliente**: Puede ver catálogo y realizar compras

### Usuarios de Prueba

| Email | Password | Rol |
|-------|----------|-----|
| admin@laneriamariano.com | admin123 | administrador |
| vendedor@laneriamariano.com | vendedor123 | vendedor |

---

## 📚 Endpoints Principales

### Productos
- `GET /api/v1/productos` - Listar productos (público)
- `GET /api/v1/productos/{id}` - Ver detalle
- `POST /api/v1/productos` - Crear producto 🔒
- `PUT /api/v1/productos/{id}` - Actualizar producto 🔒
- `DELETE /api/v1/productos/{id}` - Eliminar producto 🔒

### Inventario
- `GET /api/v1/inventario` - Listar inventario 🔒
- `GET /api/v1/inventario/alertas/stock-bajo` - Alertas 🔒
- `PUT /api/v1/inventario/{id}/actualizar-stock` - Actualizar stock 🔒

### Ventas
- `GET /api/v1/ventas` - Listar ventas 🔒
- `POST /api/v1/ventas` - Crear venta 🔒
- `GET /api/v1/ventas/{id}` - Ver detalle 🔒
- `GET /api/v1/ventas/estadisticas/general` - Estadísticas 🔒

### Clientes
- `GET /api/v1/clientes` - Listar clientes 🔒
- `POST /api/v1/clientes` - Crear cliente 🔒
- `PUT /api/v1/clientes/{id}` - Actualizar cliente 🔒

### Reportes
- `GET /api/v1/reportes/dashboard` - Dashboard general 🔒
- `GET /api/v1/reportes/productos-mas-vendidos` - Top productos 🔒
- `GET /api/v1/reportes/inventario` - Reporte de inventario 🔒

🔒 = Requiere autenticación

---

## 📄 Colección de Postman

Importa el archivo `postman_collection.json` en Postman para probar todos los endpoints.

---

## 🗄️ Estructura de Base de Datos

### Tablas Principales
- `users` - Usuarios del sistema
- `productos` - Catálogo de productos
- `inventarios` - Control de stock
- `clientes` - Información de clientes
- `ventas` - Registro de ventas
- `detalle_ventas` - Productos vendidos
- `proveedores` - Información de proveedores
- `carritos` - Carritos de compra
- `comprobantes` - Comprobantes de venta

---

## ⚙️ Configuración Adicional

### CORS
Configurado en `config/cors.php` para permitir peticiones desde:
- `http://localhost:4200` (Angular)

### Rate Limiting
- API: 60 peticiones por minuto

---

## 🧪 Testing
```bash
# Ejecutar tests
php artisan test

# Con coverage
php artisan test --coverage
```

---

## 📝 Notas de Desarrollo

### Convenciones de Código
- Controladores: PascalCase + "Controller"
- Modelos: PascalCase, singular
- Métodos: camelCase
- Variables: camelCase
- Rutas API: kebab-case

### Respuestas API Estandarizadas
```json
{
  "success": true,
  "message": "Mensaje descriptivo",
  "data": { ... }
}
```

---

## 👨‍💻 Equipo de Desarrollo

- **Backend Developer:** Ronaldo León Herhuay
- **Líder Técnico:** Iggor Adilsson Díaz Bernaola

---

## 📞 Soporte

Para problemas o dudas, contactar al equipo de desarrollo.

---

## 📄 Licencia

Proyecto académico - Universidad Nacional José María Arguedas