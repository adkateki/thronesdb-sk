FROM composer:1.10.17 as php-builder
COPY . /app
WORKDIR /app
RUN composer install

FROM node:10.22.1-stretch as node-builder
COPY --from=php-builder /app /app
WORKDIR /app
RUN npm install

FROM php:7-fpm as php-setup
COPY --from=node-builder /app /app
WORKDIR /app
RUN php bin/console bazinga:js-translation:dump assets/js
RUN php bin/console fos:js-routing:dump --target=public/js/fos_js_routes.js

FROM php:7-fpm as php-interpreter
COPY --from=php-setup /app /var/www/thronesdb
RUN docker-php-ext-install pdo pdo_mysql
RUN chown -R www-data:www-data /var/www/thronesdb
WORKDIR /var/www/thronesdb
