# ===== Stage 1: base build =====
FROM php:8.2-fpm-bookworm AS base

# System deps
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libonig-dev libxml2-dev \
 && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# OPcache (aktifkan & set)
RUN docker-php-ext-install opcache
COPY docker/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www

# ===== Stage 2: composer deps (cached) =====
FROM base AS vendor
COPY composer.json composer.lock ./
# Composer dari image resmi
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction --no-scripts --optimize-autoloader

# ===== Stage 3: production image =====
FROM base AS app

# Copy vendor dari stage vendor
COPY --from=vendor /var/www/vendor /var/www/vendor

# Copy source code
COPY . .

# Permissions (writable dirs)
RUN chown -R www-data:www-data storage bootstrap/cache

# (Opsional) siapkan php.ini produksi tambahan
COPY docker/php/conf.d/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Jangan cache config/route saat build; lakukan di entrypoint (env-aware)
# Expose FPM port
EXPOSE 9000

# Jalankan sebagai www-data
USER www-data

# Entrypoint: optimize & storage:link saat container start (env sudah ada)
USER root
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
USER www-data

CMD ["/entrypoint.sh"]
