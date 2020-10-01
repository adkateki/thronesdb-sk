FROM composer as php-builder
COPY . /app
WORKDIR /app
RUN composer install

FROM node:10.22.1-stretch as node-builder
COPY --from=php-builder /app /app
WORKDIR /app
RUN npm install

FROM php:7-fpm as php-interpreter
COPY --from=node-builder /app /app
RUN docker-php-ext-install pdo pdo_mysql
