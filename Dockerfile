# MediAgenda para Render (plan gratuito, Docker).
# Imagen oficial de PHP con Apache. La base de datos y los archivos viven en Supabase;
# este contenedor no guarda nada propio (su disco se borra en cada reinicio).

# 1) Dependencias de PHP sin las de desarrollo.
FROM composer:2 AS dependencias
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# 2) Servidor web.
FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libicu-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl gd opcache \
    && rm -rf /var/lib/apt/lists/*

# Endurecimiento de PHP y Apache: sin versiones en las cabeceras ni listados de carpetas.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-mediagenda.ini"
COPY docker/apache.conf /etc/apache2/conf-available/mediagenda.conf
RUN a2enmod rewrite headers \
    && a2enconf mediagenda \
    && sed -ri 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY --from=dependencias /app /var/www/html

# Laravel solo escribe en storage/ y bootstrap/cache/; el resto queda de solo lectura.
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R u=rwX,g=rX,o= storage bootstrap/cache \
    && chmod +x docker/entrypoint.sh

ENV PORT=10000
EXPOSE 10000

ENTRYPOINT ["docker/entrypoint.sh"]
