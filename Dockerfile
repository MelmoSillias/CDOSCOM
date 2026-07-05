#syntax=docker/dockerfile:1

FROM dunglas/frankenphp:1-php8.3-bookworm AS frankenphp_base

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends file git \
    && install-php-extensions \
        @composer \
        apcu \
        intl \
        opcache \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"

COPY docker/frankenphp/conf.d/10-app.ini $PHP_INI_DIR/app.conf.d/
COPY docker/frankenphp/conf.d/20-app.prod.ini $PHP_INI_DIR/app.conf.d/
COPY docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile
COPY --chmod=755 docker/frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

ENTRYPOINT ["docker-entrypoint"]
HEALTHCHECK --start-period=60s CMD php -r 'exit(false === @file_get_contents("http://localhost/", context: stream_context_create(["http" => ["timeout" => 5]])) ? 1 : 0);'
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]

FROM frankenphp_base AS frankenphp_prod

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV SERVER_NAME=:80

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

COPY . ./

RUN mkdir -p public/uploads var/cache var/log \
    && composer dump-autoload --classmap-authoritative --no-dev \
    && APP_SECRET=build-time-placeholder composer run-script --no-dev post-install-cmd \
    && APP_SECRET=build-time-placeholder php bin/console asset-map:compile \
    && chown -R www-data:www-data var public/uploads
