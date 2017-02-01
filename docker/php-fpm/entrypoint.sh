#!/bin/bash

touch /logs/monolog.log

chown www-data:www-data /logs/monolog.log

php-fpm