FROM composer as php-builder
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

FROM node:10.22.1-stretch as node-gulp
COPY --from=php-setup /app /app
WORKDIR /app
RUN npm install -g gulp
RUN gulp
RUN gulp vendor
RUN gulp translations

FROM php:7-fpm as php-interpreter
COPY --from=node-gulp /app /var/www/thronesdb-sk
RUN docker-php-ext-install pdo pdo_mysql
RUN chown -R www-data:www-data /var/www/thronesdb-sk
WORKDIR /var/www/thronesdb-sk
