FROM php:5.6-fpm

# install phpredis extension
ENV PHPREDIS_VERSION 2.2.7

RUN apt-get update && apt-get install -y \
    git \
    # This package is required for mongo
    libssl-dev \
    # Phalcon
    && git clone --depth=1 -b 2.0.x git://github.com/phalcon/cphalcon.git /usr/local/src/cphalcon \
    && cd /usr/local/src/cphalcon/build && ./install \
    && echo "extension=phalcon.so" > /usr/local/etc/php/conf.d/25-phalcon.ini \
    # install ed25519
    && git clone git://github.com/wedancedalot/php-ed25519-ext.git \
    && cd php-ed25519-ext && phpize && ./configure && make && make install \
    && echo "extension=ed25519.so" > /usr/local/etc/php/conf.d/30-ed25519.ini

# Inject php conf
COPY config/php.ini /usr/local/etc/php/