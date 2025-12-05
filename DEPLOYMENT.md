# 📦 Guía de Deployment - Lanería Mariano Díaz Backend

## 🎯 Requisitos del Servidor

### Software Necesario
- PHP 8.1 o superior
- SQL Server 2019 o superior
- Composer
- Git
- Servidor web (Apache/Nginx)

### Extensiones PHP Requeridas
```bash
php -m | grep -E 'pdo_sqlsrv|sqlsrv|mbstring|openssl|tokenizer|xml'
```

---

## 🚀 Pasos de Deployment

### 1. Clonar Repositorio
```bash
cd /var/www
git clone <url-repositorio> laneria-backend
cd laneria-backend
```

### 2. Configurar Permisos
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 3. Instalar Dependencias
```bash
composer install --no-dev --optimize-autoloader
```

### 4. Configurar Variables de Entorno
```bash
cp .env.example .env
nano .env
```

Configurar:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

DB_CONNECTION=sqlsrv
DB_HOST=tu_servidor_sql
DB_DATABASE=laneria_mariano_db
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### 5. Generar Key
```bash
php artisan key:generate
```

### 6. Ejecutar Migraciones
```bash
php artisan migrate --force
```

### 7. Optimizar
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 8. Configurar SSL
Instalar certificado SSL (Let's Encrypt recomendado)

### 9. Configurar CRON (opcional)
```bash
crontab -e
```

Agregar:
```
* * * * * cd /var/www/laneria-backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔧 Configuración de Apache

Crear virtual host `/etc/apache2/sites-available/laneria.conf`:
```apache
<VirtualHost *:80>
    ServerName api.tudominio.com
    DocumentRoot /var/www/laneria-backend/public

    <Directory /var/www/laneria-backend/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/laneria-error.log
    CustomLog ${APACHE_LOG_DIR}/laneria-access.log combined
</VirtualHost>
```

Activar:
```bash
a2ensite laneria
a2enmod rewrite
systemctl reload apache2
```

---

## 🔒 Seguridad

### 1. Firewall
```bash
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

### 2. Fail2Ban (opcional)
```bash
apt install fail2ban
systemctl enable fail2ban
```

### 3. Ocultar información de PHP
En `php.ini`:
```ini
expose_php = Off
```

---

## 📊 Monitoreo

### Logs
```bash
tail -f storage/logs/laravel.log
```

### Performance
```bash
php artisan optimize
```

---

## 🔄 Actualizar Aplicación

Usar el script:
```bash
./deploy.sh
```

O manualmente:
```bash
git pull
composer install --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

---

## ✅ Verificación Post-Deployment

1. Probar login: `POST /api/v1/auth/login`
2. Verificar productos: `GET /api/v1/productos`
3. Revisar logs: `storage/logs/laravel.log`
4. Comprobar SSL: `https://tudominio.com`

---

## 🆘 Troubleshooting

### Error 500
```bash
php artisan config:clear
chmod -R 755 storage
```

### Error de Base de Datos
```bash
php artisan migrate:status
php artisan config:cache
```

### Cache no actualiza
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```