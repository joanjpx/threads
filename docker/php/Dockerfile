FROM php:8.3-fpm-alpine

# Dependencias del sistema
RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    libxml2-dev \
    linux-headers \
    $PHPIZE_DEPS

# Extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        sockets \
        opcache

# Redis extension (via PECL)
RUN pecl install redis && docker-php-ext-enable redis

# rdkafka extension (para Kafka)
RUN apk add --no-cache librdkafka-dev \
    && pecl install rdkafka \
    && docker-php-ext-enable rdkafka

# Instalar php-spx profiling extension
RUN apk add --no-cache zlib-dev \
    && git clone https://github.com/NoiseByNorthwest/php-spx.git \
    && cd php-spx \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && docker-php-ext-enable spx \
    && cd .. && rm -rf php-spx

# Configurar SPX localmente insertando un conf para php
RUN echo "spx.http_enabled=1" >> /usr/local/etc/php/conf.d/docker-php-ext-spx.ini \
    && echo "spx.http_key=\"dev\"" >> /usr/local/etc/php/conf.d/docker-php-ext-spx.ini \
    && echo "spx.http_ip_whitelist=\"*\"" >> /usr/local/etc/php/conf.d/docker-php-ext-spx.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar código
COPY . .

# Instalar dependencias PHP
RUN composer install --no-interaction --optimize-autoloader --no-dev --no-scripts

# Permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]