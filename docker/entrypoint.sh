#!/bin/sh
# Arranque del contenedor en Render.
# Render indica el puerto en $PORT; Apache se adapta a él.
set -e

PORT="${PORT:-10000}"
sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Cachés de configuración, rutas y vistas con las variables de entorno de Render.
# Las migraciones NO corren aquí: en Render solo existe el usuario de la aplicación,
# que no puede cambiar la estructura de la base (privilegio mínimo).
su -s /bin/sh www-data -c "php artisan optimize && php artisan filament:optimize"

exec apache2-foreground
