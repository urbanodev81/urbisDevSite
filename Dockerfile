FROM php:8.4-fpm-alpine

# PHP 8.4 + Postgres desde 03/11/2026 (pendência 246, painel): o site deixou de
# ser "sem banco". `pdo_pgsql` é o banco do painel; `postgresql17-client` dá o
# `pg_dump` que o backup e o dump antes de migrar usam; `zip` é do composer.
# `gd` com WebP/JPEG (04/11/2026, bloco 3): o painel reencoda as imagens do
# blog — reduz e apaga o EXIF (GPS do celular) antes de publicar; `exif` só
# para ler a orientação da foto e endireitá-la antes de jogar o EXIF fora.
RUN apk add --no-cache \
    icu-dev libzip-dev postgresql-dev postgresql17-client curl \
    libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install intl pdo pdo_pgsql zip opcache gd exif

# Foto do blog até 8 MB (a regra do `TextoController::imagem`). O padrão do PHP
# é 2 MB por arquivo: a foto do celular morria com 413 antes de chegar ao
# Laravel (05/11/2026). Os dois nginx (o do contêiner e o do HOST) levam
# `client_max_body_size 10m` pelo mesmo motivo — mexeu num, mexa nos três.
# A memória sobe para 256 MB porque o GD abre a foto inteira: 8000x8000 px
# (o teto da validação) passa de 128 MB sozinho.
RUN printf 'upload_max_filesize=10M\npost_max_size=12M\nmemory_limit=256M\n' > /usr/local/etc/php/conf.d/uploads.ini

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
