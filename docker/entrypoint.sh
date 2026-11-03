#!/bin/sh
set -eu

PORT="${PORT:-10000}"
APP_ROOT="/app"

cd "$APP_ROOT"

mkdir -p \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache

export PORT
export SERVER_NAME=":${PORT}"

if [ -z "${APP_KEY:-}" ]; then
  echo "ERROR: APP_KEY is not set. Generate one with: php artisan key:generate --show" >&2
  exit 1
fi

# Optional caches after env vars are present (enable on Render with CACHE_CONFIG=true)
if [ "${CACHE_CONFIG:-false}" = "true" ]; then
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache || true
  chown -R www-data:www-data bootstrap/cache storage 2>/dev/null || true
fi

# Optional migrate — OFF by default so container restarts do not mutate the DB
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  echo "Running php artisan migrate --force ..."
  php artisan migrate --force
fi

# Seeding is never automatic. Use Render Shell:
#   php artisan db:seed --force

if [ "$#" -eq 0 ]; then
  set -- frankenphp run --config /etc/caddy/Caddyfile
fi

exec "$@"
