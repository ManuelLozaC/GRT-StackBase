#!/bin/sh
set -e

mkdir -p /var/www/backend/storage/app/public
mkdir -p /var/www/backend/storage/framework/cache/data
mkdir -p /var/www/backend/storage/framework/sessions
mkdir -p /var/www/backend/storage/framework/testing
mkdir -p /var/www/backend/storage/framework/views
mkdir -p /var/www/backend/bootstrap/cache

exec docker-php-entrypoint "$@"
