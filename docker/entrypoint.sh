#!/usr/bin/env bash
set -e

php -v

# pastikan storage link ada
php artisan storage:link || true

# optimize caches (env-aware)
php artisan optimize

exec php-fpm
