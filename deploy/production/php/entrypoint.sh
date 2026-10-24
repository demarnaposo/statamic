#!/bin/sh
# Entry point for every role of the platform image (app, admin, queue, scheduler, migrate).
# Caches are rebuilt on each start from this container's own env (CP_ENABLED differs per role) into
# bootstrap/cache and VIEW_COMPILED_PATH (tmpfs in compose). Migrations are NOT run here: see the `migrate` service.
set -e
cd /srv/app

: "${FPM_MAX_CHILDREN:=10}"
export FPM_MAX_CHILDREN

mkdir -p "${VIEW_COMPILED_PATH:-/tmp/views}" storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs storage/app/public 2>/dev/null || true

if [ "${APP_OPTIMIZE:-true}" = "true" ]; then
    php artisan optimize --no-interaction
fi
[ -e public/storage ] || php artisan storage:link --no-interaction >/dev/null 2>&1 || true

exec "$@"
