FROM php:8.4-fpm-alpine

# 1. Instalar dependências do sistema via APK (Gerador de pacotes do Alpine)
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

# 2. Instalar as extensões padrão do PHP exigidas pelo Laravel e Filament
RUN docker-php-ext-install pdo pdo_pgsql bcmath gd intl zip

# 3. Instalar e ativar a extensão Redis nativa de forma compatível com Alpine
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        linux-headers \
    && pecl channel-update pecl.php.net \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# 4. Importar o Composer oficial mais recente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Definir diretoria de trabalho dentro do contentor
WORKDIR /var/www

# 6. Garantir permissões corretas para o Laravel manipular a cache e logs
RUN chmod -R 775 /var/www

# 7. Expor a porta de comunicação do painel
EXPOSE 8000

# 8. O comando de arranque deve ser SEMPRE a última instrução do ficheiro
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]