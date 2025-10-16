FROM php:8.4-fpm-bookworm

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    unzip \
    cmake \
    make \
    build-essential \
    libssl-dev \
    wget \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip opcache

# Сборка и установка rabbitmq-c
RUN cd /tmp && \
    git clone --recurse-submodules https://github.com/alanxz/rabbitmq-c.git && \
    cd rabbitmq-c && \
    mkdir build && cd build && \
    cmake .. && cmake --build . --target install && \
    ldconfig && \
    cd / && rm -rf /tmp/rabbitmq-c

# Установка расширения amqp
RUN pecl install amqp && docker-php-ext-enable amqp

# Установка Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug
COPY ./php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/app
COPY .. .

# Настройка прав
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var \
    && chmod -R 777 var/cache var/log