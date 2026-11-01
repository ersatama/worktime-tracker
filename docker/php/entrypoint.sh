#!/bin/sh
set -e

cd /var/www/html

# No APP_KEY given: make one once and keep it in the storage volume, so sessions
# survive restarts without asking the reviewer to generate a key by hand.
if [ -z "$APP_KEY" ]; then
    KEY_FILE=storage/app/.app_key
    [ -s "$KEY_FILE" ] || php artisan key:generate --show > "$KEY_FILE"
    APP_KEY="$(cat "$KEY_FILE")"
    export APP_KEY
fi

if [ "$1" = "php-fpm" ]; then
    chown -R www-data:www-data storage bootstrap/cache
    php artisan migrate --force
    php artisan optimize
fi

exec "$@"
