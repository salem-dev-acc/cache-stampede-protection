FROM php:7.1-fpm

# Install recommended extensions for Symfony
RUN apt-get update && apt-get install -y \
        libicu-dev \
    && docker-php-ext-install \
        intl \
        opcache \
    && docker-php-ext-enable \
        intl \
        opcache

RUN pecl install -o -f redis \
    &&  rm -rf /tmp/pear \
    &&  docker-php-ext-enable redis

COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.conf
COPY docker/php/www.conf /usr/local/etc/pool.d/www.conf
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini

RUN apt install -y htop

# Permission fix
RUN usermod -u 1000 www-data
