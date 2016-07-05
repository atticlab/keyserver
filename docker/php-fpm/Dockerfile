FROM php:5.6-fpm

# install phpredis extension
ENV PHPREDIS_VERSION 2.2.7

RUN apt-get update && apt-get install -y \
    git \
    # This package is required for mongo
    libssl-dev \
    # Phalcon
    && git clone --depth=1 git://github.com/phalcon/cphalcon.git /usr/local/src/cphalcon \
    && cd /usr/local/src/cphalcon/build && ./install \
    && echo "extension=phalcon.so" > /usr/local/etc/php/conf.d/25-phalcon.ini \
    # Composer
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Inject php conf
COPY config/php.ini /usr/local/etc/php/