#!/bin/sh
# Container entrypoint: prepare writable state, publish assets for Caddy, then
# hand over to whatever the container was asked to run (php-fpm, or the
# scheduler in the sidecar).
set -e

# Laravel needs these to exist; they live on a volume, so the image cannot ship
# them pre-created.
mkdir -p \
    /srv/storage/app/private \
    /srv/storage/app/public \
    /srv/storage/framework/cache/data \
    /srv/storage/framework/sessions \
    /srv/storage/framework/views \
    /srv/storage/logs \
    /srv/bootstrap/cache

# Republish this release's public/ into the volume Caddy serves. Without the
# delete, a redeploy would leave the previous release's hashed assets behind.
rm -rf /srv/public/build
cp -R /opt/app-public/. /srv/public/

# Only the web container should run migrations and warm caches; the scheduler
# sidecar shares this image and would otherwise race it on every deploy.
if [ "${RUN_RELEASE_TASKS:-false}" = "true" ]; then
    # Not --isolated: that takes a lock through the cache store, and on a fresh
    # database the cache table it needs has not been migrated yet. The
    # RUN_RELEASE_TASKS gate above already means only one container gets here.
    php artisan migrate --force
    php artisan optimize
fi

exec "$@"
