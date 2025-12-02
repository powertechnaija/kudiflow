FROM php:8.2-fpm-alpine

# System dependencies
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

# PHP extensions
RUN docker-php-ext-configure zip \
    && docker-php-ext-install zip pdo pdo_sqlite

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy app
COPY . .

# Install dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Permissions for storage + cache
RUN mkdir -p /app/storage /app/bootstrap/cache \
    && chmod -R 777 /app/storage /app/bootstrap/cache

# Create SQLite database file
RUN mkdir -p /app/database \
    && touch /app/database/database.sqlite \
    && chmod 777 /app/database/database.sqlite

# Expose port
EXPOSE 8000

# Start script that runs artisan commands safely
COPY ./docker/start.sh /start.sh
RUN chmod +x /start.sh

CMD ["/start.sh"]
