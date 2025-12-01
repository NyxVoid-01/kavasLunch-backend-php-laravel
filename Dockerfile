FROM php:8.3-fpm

# Instalar dependencias del sistema (incluyendo supervisor)
RUN apt-get update && apt-get install -y \
    libpq-dev git unzip zip curl postgresql-client \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    libmagickwand-dev supervisor --no-install-recommends && \
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

# Configurar PHP-FPM
COPY docker/php/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

# Configurar supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN mkdir -p /var/log/supervisor && \
    chown -R laravel:laravel /var/log/supervisor

# Copiar scripts de inicialización
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]