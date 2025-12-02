#!/usr/bin/env bash
set -e

# Default DB path if Railway does not override
: "${DB_DATABASE:=/var/www/html/database/database.sqlite}"

echo "Using SQLite database at: $DB_DATABASE"

# Ensure db directory exists
mkdir -p "$(dirname "$DB_DATABASE")"

# Create DB file if missing
if [ ! -f "$DB_DATABASE" ]; then
    echo "Creating SQLite database file..."
    touch "$DB_DATABASE"
fi

# Fix Laravel directories
chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    "$(dirname "$DB_DATABASE")"

chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    "$(dirname "$DB_DATABASE")"

# Generate APP_KEY if missing
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY missing — generating one..."
    php artisan key:generate --force
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force || echo "Migration step failed"

# Run seeders
echo "Running seeders..."
php artisan db:seed --force || echo "Seeding failed or skipped"

# Start Apache
echo "Starting Apache..."
exec "$@"
