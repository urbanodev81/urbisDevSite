FROM php:8.4-fpm-alpine

# PHP 8.4 + Postgres desde 03/11/2026 (pendência 246, painel): o site deixou de
# ser "sem banco". `pdo_pgsql` é o banco do painel; `postgresql17-client` dá o
# `pg_dump` que o backup e o dump antes de migrar usam; `zip` é do composer.
RUN apk add --no-cache \
    icu-dev libzip-dev postgresql-dev postgresql17-client curl \
    && docker-php-ext-install intl pdo pdo_pgsql zip opcache

COPY src/ /var/www/html/

WORKDIR /var/www/html

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --no-interaction --optimize-autoloader

RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    storage/app/private \
    bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
