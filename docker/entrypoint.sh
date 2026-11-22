#!/bin/sh
set -eu

PORT="${PORT:-10000}"
# Render's PORT defaults to 10000; keep Apache bound to the assigned port.
sed -i "s/^Listen 10000$/Listen ${PORT}/" /etc/apache2/ports.conf

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

if [ -L public/storage ]; then
    rm -f public/storage
elif [ -e public/storage ]; then
    echo "Warning: public/storage exists and is not a symlink; leaving it untouched."
else
    php artisan storage:link || true
fi

php artisan config:cache

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
