# Use PHP 8.2 FPM Debian as base image
FROM php:8.2-fpm-bookworm AS base

# Install system dependencies
RUN apt-get update && apt-get install -y \
   git \
   curl \
   libpng-dev \
   libonig-dev \
   libxml2-dev \
   zip \
   unzip \
   libzip-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-interaction --optimize-autoloader &&     rm -rf vendor/laravel/pail &&     composer dump-autoload --optimize

# Create the storage link
RUN php artisan storage:link

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Document that Laravel runs on port 9000 by default
EXPOSE 8000

# Start PHP-FPM
CMD ["php-fpm"]
