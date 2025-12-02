# ====================================================================
#   Laravel + PHP 8.2 + Apache + SQLite
#   Production-Ready Dockerfile for Railway.app
# ====================================================================

FROM php:8.2-apache

# ------------------------------------------------------------
# Install system packages & PHP extensions
# ------------------------------------------------------------
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    sqlite3 \
    libzip-dev \
    && docker-php-ext-configure zip --with-libzip \
    && docker-php-ext-install zip pdo pdo_sqlite

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# ------------------------------------------------------------
# Set working directory
# ------------------------------------------------------------
WORKDIR /var/www/html

# ------------------------------------------------------------
# Install Composer
# ------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy composer files first (layer caching)
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress

# ------------------------------------------------------------
# Copy entire application
# ------------------------------------------------------------
COPY . .

# ------------------------------------------------------------
# Fix permissions required by Laravel
# ------------------------------------------------------------
RUN chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

# Ensure database directory exists
RUN mkdir -p /var/www/html/database

# ------------------------------------------------------------
# Copy entrypoint script (handles sqlite creation, migrations, seeding)
# ------------------------------------------------------------
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
