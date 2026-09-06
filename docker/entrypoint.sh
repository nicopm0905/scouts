#!/usr/bin/env sh
set -e

# Cachés de Laravel (se generan aquí porque necesitan las variables de entorno de Render)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migraciones (idempotente; nunca borra datos)
php artisan migrate --force --no-interaction || true

# Roles/permisos + cuenta de coordinación (idempotente)
php artisan db:seed --class=ProductionSeeder --force --no-interaction || true

# Enlace de almacenamiento público
php artisan storage:link || true

# Servidor. Render inyecta $PORT; en local usa 8000.
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
