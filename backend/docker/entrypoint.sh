#!/bin/bash
set -e
cd /var/www/html
[ -f .env ] || cp .env.example .env
grep -qE '^APP_KEY=base64:' .env || php artisan key:generate --force
until php -r "new PDO('mysql:host='.getenv('DB_HOST').';port=3306','$DB_USERNAME','$DB_PASSWORD');" 2>/dev/null; do
  echo "waiting for mysql..."; sleep 3
done
php artisan storage:link 2>/dev/null || true
php artisan migrate --force || true
# Freeze config (incl. APP_KEY) from .env so every request reads the cache, not the
# per-request env — php artisan serve resolves APP_KEY empty on web requests otherwise.
php artisan config:cache
php artisan route:cache || true
exec php artisan serve --host=0.0.0.0 --port=8000
