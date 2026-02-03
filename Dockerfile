FROM dunglas/frankenphp:latest AS vendor

RUN install-php-extensions @composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction --optimize-autoloader --no-scripts

COPY . ./
RUN composer dump-autoload --no-dev --classmap-authoritative --no-scripts

FROM dunglas/frankenphp:latest

RUN install-php-extensions pdo_mysql

WORKDIR /app

COPY --from=vendor /app /app
COPY Caddyfile /etc/frankenphp/Caddyfile

ENV APP_ENV=prod \
    APP_DEBUG=0

RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var
