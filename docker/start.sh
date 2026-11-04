#!/bin/sh
# Arranque del contenedor: prepara la clave, la base SQLite y levanta Apache en el puerto que pida la plataforma.
set -e
cd /app

# APP_KEY: Render genera un valor base64 de 256 bits sin el prefijo que espera Laravel.
case "$APP_KEY" in
  base64:*) ;;
  "") APP_KEY="$(php artisan key:generate --show --no-ansi)" ;;
  *) APP_KEY="base64:$APP_KEY" ;;
esac
export APP_KEY

# Base de datos SQLite (el disco del plan gratuito es efímero: se vuelve a sembrar en cada arranque).
touch database/database.sqlite
chown www-data:www-data database database/database.sqlite
php artisan migrate --force --no-interaction
if [ "$(sqlite3 database/database.sqlite 'select count(*) from users;')" = "0" ]; then
  php artisan db:seed --force --no-interaction
fi
chown -R www-data:www-data database storage bootstrap/cache

php artisan package:discover --ansi
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Puerto asignado por la plataforma (Render usa la variable PORT).
PORT="${PORT:-80}"
sed -ri "s/Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
