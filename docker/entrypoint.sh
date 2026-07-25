#!/bin/sh
set -e

# Render assigns the port and the public URL at run time.
export SERVER_NAME=":${PORT:-10000}"
export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"

php artisan migrate --force --seed

# Cache after migrating so the caches are built from the run-time environment.
php artisan optimize

exec frankenphp run --config /etc/caddy/Caddyfile --adapter caddyfile
