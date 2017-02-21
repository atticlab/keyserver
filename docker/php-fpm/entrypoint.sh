#!/bin/bash

touch /logs/monolog.log

chown www-data:www-data /logs/monolog.log

composer --working-dir=/app install

php-fpm
