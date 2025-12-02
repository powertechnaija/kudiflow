# Use official PHP 8.2 FPM with Alpine (much easier + fewer install errors)
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk update && apk add --no-cache \
    git \
    unzip \
    zip \
    sqlite \
    sqlite-dev \
    libzip \
    libzip-dev \
    oniguruma-dev \
    curl \
    bash

# Install PHP extensions
RUN docker-php-ext-configure zip
RUN docker-php-ext-install zip pdo pdo_sqlite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Work directory
WORKDIR /app

# Copy source code
COPY . .

# Install Laravel dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Ensure SQLite file exists
RUN touch /app/database/database.sqlite && chmod 777 /app/database/database.sqlite

# Run migrations + seed during build
RUN php artisan key:generate
RUN php artisan migrate --force
RUN php artisan db:seed --force

# Expose port
EXPOSE 8000

# Start Laravel using PHP server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
