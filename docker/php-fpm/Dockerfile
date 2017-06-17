FROM php:5.6-fpm

ENV http_proxy http://10.78.17.6:3128
ENV https_proxy http://10.78.17.6:3128

# install phpredis extension
ENV PHPREDIS_VERSION 2.2.7

RUN apt-get update && apt-get install -y \
    git \
    # This package is required for mongo
    libssl-dev \
    # Phalcon
    && git clone --depth=1 -b 2.0.x https://github.com/phalcon/cphalcon.git /usr/local/src/cphalcon \
    && cd /usr/local/src/cphalcon/build && ./install \
    && echo "extension=phalcon.so" > /usr/local/etc/php/conf.d/25-phalcon.ini \
    # install ed25519
    && git clone https://github.com/wedancedalot/php-ed25519-ext.git \
    && cd php-ed25519-ext && phpize && ./configure && make && make install \
    && echo "extension=ed25519.so" > /usr/local/etc/php/conf.d/30-ed25519.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Inject php conf
COPY config/php.ini /usr/local/etc/php/

COPY ./entrypoint.sh /

CMD ["/entrypoint.sh"]
