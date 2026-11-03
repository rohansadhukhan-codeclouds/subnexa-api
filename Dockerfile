# syntax=docker/dockerfile:1.7

# -----------------------------------------------------------------------------
# Stage 1: install Composer production dependencies
# -----------------------------------------------------------------------------
FROM composer:2.8 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction \
    --no-progress

COPY . .

RUN composer dump-autoload \
    --optimize \
    --classmap-authoritative \
    --no-dev \
    --no-interaction

# -----------------------------------------------------------------------------
# Stage 2: FrankenPHP (Caddy + PHP 8.3) — production HTTP server for Render
# -----------------------------------------------------------------------------
# PHP 8.4 — matches composer.lock platform (require php ^8.3, lock resolved on 8.4.x)
FROM dunglas/frankenphp:1-php8.4

ENV APP_ENV=production \
    APP_DEBUG=false \
    PORT=10000 \
    SERVER_NAME=":10000"

WORKDIR /app

# FrankenPHP already ships mbstring/opcache/curl/PDO/mysqlnd.
# Add MySQL PDO driver (required). Postgres is optional — install when needed:
#   RUN install-php-extensions pdo_pgsql
RUN install-php-extensions pdo_mysql

RUN printf '%s\n' \
    'opcache.enable=1' \
    'opcache.memory_consumption=128' \
    'opcache.interned_strings_buffer=16' \
    'opcache.max_accelerated_files=10000' \
    'opcache.validate_timestamps=0' \
    'opcache.revalidate_freq=0' \
    > /usr/local/etc/php/conf.d/opcache-production.ini

COPY --from=vendor --chown=www-data:www-data /app /app
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/Caddyfile /etc/caddy/Caddyfile

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache \
    && rm -f .env .env.backup .env.production \
    && php -r "file_exists('vendor/autoload.php') || throw new Exception('vendor missing');"

EXPOSE 10000

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD curl -fsS http://127.0.0.1:10000/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
