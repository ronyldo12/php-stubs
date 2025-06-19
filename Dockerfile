FROM composer:2 as composer
FROM php:7.4-cli

# Install dependencies
RUN apt-get update \
    && apt-get install -y git unzip libxml2-dev libzip-dev zlib1g-dev \
    && docker-php-ext-install zip

# Install runkit7 (latest available: 4.0.0a6)
RUN pecl install runkit7-4.0.0a6 \
    && docker-php-ext-enable runkit7

# Install Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . /app

RUN composer install

ENTRYPOINT ["/bin/bash"] 