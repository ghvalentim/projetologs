FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
 bash \
 curl \
 libpng-dev \
 libxml2-dev \
 zip \
 unzip \
 libpq-dev \
 icu-dev \
 libzip-dev 
 
 RUN docker-php-ext-install pdo pdo_pgsql bcmath gd intl zip

 COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

 WORKDIR /var/www

 RUN chmod -R 775 /var/www

 EXPOSE 8000

 CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]