# syntax=docker/dockerfile:1

# --- Front-end assets ---------------------------------------------------------
FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# --- PHP dependencies ---------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

FROM vendor AS vendor-dev
RUN composer install --no-interaction --prefer-dist --no-scripts --ignore-platform-reqs \
    && composer dump-autoload --optimize --no-scripts

# --- PHP-FPM application ------------------------------------------------------
FROM php:8.4-fpm-alpine AS app
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql redis opcache intl pcntl
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build
RUN php artisan package:discover --ansi

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# --- Test runner (dev dependencies, runs against MySQL + Redis) ---------------
FROM app AS tests
COPY --from=vendor-dev --chown=www-data:www-data /app/vendor ./vendor
RUN php artisan package:discover --ansi
ENTRYPOINT []
# `artisan test` (Collision) wants a .env file, which the image deliberately has none of.
CMD ["php", "vendor/bin/phpunit", "--testdox"]

# --- Nginx serving public/ and passing PHP to the app -------------------------
FROM nginx:1.27-alpine AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
