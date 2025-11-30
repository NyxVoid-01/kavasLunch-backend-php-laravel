FROM php:8.3-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpq-dev git unzip zip curl postgresql-client \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    libmagickwand-dev --no-install-recommends && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install pdo pdo_pgsql gd && \
    pecl install redis imagick && \
    docker-php-ext-enable redis imagick && \
    rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Crear usuario laravel con UID/GID configurables
ARG UID=1000
ARG GID=1000
RUN groupadd -g ${GID} laravel && \
    useradd -u ${UID} -g laravel -m laravel

WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY . .

# Configurar permisos para Laravel
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Cambiar al usuario laravel
USER laravel

# Instalar dependencias como usuario laravel
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

EXPOSE 9000
CMD ["php-fpm"]