# Production image for Specula. Local development uses Sail's compose.yaml
# instead; this is the artifact that ships to the droplet.
#
# Build:  docker build -t specula:latest .
# Run:    see compose.prod.yaml

# --- Composer dependencies -------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# --- Frontend assets -------------------------------------------------------
# Built on a PHP image rather than a plain Node one: the Wayfinder Vite plugin
# shells out to `php artisan wayfinder:generate` during the build.
FROM php:8.5-cli-alpine AS assets

RUN apk add --no-cache nodejs npm

WORKDIR /srv
COPY --from=vendor /app/vendor ./vendor
COPY . .

# The Wayfinder plugin boots Laravel to read the route list, which needs an
# env file present. Real configuration is injected at runtime, never baked in.
# storage/framework is excluded from the build context (it is runtime state),
# but Laravel still wants the cache paths to exist in order to boot.
RUN mkdir -p storage/framework/views storage/framework/cache/data \
        storage/framework/sessions storage/logs bootstrap/cache \
    && cp .env.example .env \
    && npm ci \
    && npm run build \
    && rm .env

# --- Runtime ---------------------------------------------------------------
FROM php:8.5-fpm-alpine

RUN apk add --no-cache postgresql-client libpq icu-libs libzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS postgresql-dev icu-dev libzip-dev linux-headers \
    # opcache is not listed: the PHP 8.5 image already ships it built in, and
    # asking docker-php-ext-install for it builds no module and then fails.
    # deploy/php.ini still configures it.
    && docker-php-ext-install pdo_pgsql pgsql intl zip \
    && apk del .build-deps

COPY deploy/php.ini /usr/local/etc/php/conf.d/specula.ini

WORKDIR /srv

COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /srv/public/build ./public/build
COPY . .

# public/ is served by Caddy out of a shared volume, so keep a pristine copy in
# the image. The entrypoint syncs it on every boot, otherwise a redeploy would
# leave the volume holding the previous release's assets.
RUN cp -R /srv/public /opt/app-public \
    && addgroup -g 1000 specula \
    && adduser -u 1000 -G specula -s /bin/sh -D specula \
    && chown -R specula:specula /srv

COPY deploy/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

USER specula

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
