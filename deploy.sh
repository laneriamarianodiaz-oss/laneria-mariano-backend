#!/bin/bash

echo "🚀 Iniciando deployment..."

# 1. Actualizar código
echo "📥 Actualizando código desde repositorio..."
git pull origin main

# 2. Instalar dependencias
echo "📦 Instalando dependencias..."
composer install --no-dev --optimize-autoloader

# 3. Limpiar caché
echo "🧹 Limpiando caché..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 4. Optimizar
echo "⚡ Optimizando aplicación..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Ejecutar migraciones (con confirmación)
echo "🗄️ ¿Ejecutar migraciones? (y/n)"
read -r respuesta
if [ "$respuesta" = "y" ]; then
    php artisan migrate --force
fi

# 6. Reiniciar servicios
echo "🔄 Reiniciando servicios..."
php artisan queue:restart

echo "✅ Deployment completado exitosamente!"