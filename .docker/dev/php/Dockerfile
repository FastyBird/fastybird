FROM composer:2.4 AS composer

FROM php:8.2-fpm-alpine

ARG PHP_VERSION=8.2

# Install dependencies
RUN apk update --no-cache && apk add --no-cache \
    bash \
    curl \
    unzip \
    zip \
    bzip2 \
    python3 \
    g++ \
    make \
    git \
    nginx \
    yarn \
    icu-dev \
    gmp-dev \
    zlib-dev \
    libxml2-dev \
    oniguruma-dev \
    postgresql-dev \
    curl-dev \
    linux-headers \
    libxslt-dev \
    libzip-dev \
    autoconf

# Install PHP extensions
RUN docker-php-ext-configure intl \
    && docker-php-ext-install \
    bcmath \
    calendar \
    ctype \
    curl \
    dom \
    exif \
    fileinfo \
    gmp \
    intl \
    mbstring \
    mysqli \
    opcache \
    pcntl \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    phar \
    simplexml \
    sockets \
    xml \
    xmlwriter \
    xsl \
    zip

# Install APCu extension
RUN pecl install apcu \
    && docker-php-ext-enable apcu

# Install YAML extension
RUN apk add --no-cache yaml-dev \
    && pecl install yaml \
    && docker-php-ext-enable yaml

# Install Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Clean up
RUN rm -rf /var/lib/apk/lists/* /tmp/* /var/tmp/* /usr/share/doc/* /usr/share/man/* /var/cache/apk/*

COPY .docker/dev/php/conf/php-fpm.conf      /etc/php${PHP_VERSION}/php-fpm.conf
COPY .docker/dev/php/conf/php.ini           /etc/php${PHP_VERSION}/php.ini

# Set the working directory
WORKDIR /app

EXPOSE 9000
