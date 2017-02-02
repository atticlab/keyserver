#!/bin/bash

touch /logs/monolog.log

chown www-data:www-data /logs/monolog.log

composer --working-dir=/app install

export http_proxy=''
export https_proxy=''

php-fpm
