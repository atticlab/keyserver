#!/bin/bash

touch /logs/monolog.log

chown www-data:www-data /logs/monolog.log

export http_proxy=''
export https_proxy=''

php-fpm