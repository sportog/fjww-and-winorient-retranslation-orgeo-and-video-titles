FROM php:8.0-fpm-alpine3.16

WORKDIR /var/www

RUN mkdir -p /usr/src/php/ext/redis; \
    curl -fsSL --ipv4 https://github.com/phpredis/phpredis/archive/5.3.2.tar.gz | tar xvz -C "/usr/src/php/ext/redis" --strip 1; \
    docker-php-ext-install redis

RUN docker-php-ext-install mysqli