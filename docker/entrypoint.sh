#!/usr/bin/env bash
set -e

# Default DB path if not provided
: "${DB_DATABASE:=/var/www/html/database/database.sqlite}"

echo "Using DB file: $DB_DATABASE"

# Create SQLite database file if it doesn't exist
mkdir -p "$(dirname "$DB_DATABASE")"

if [ ! -f "$DB_DATABASE" ]; then
  echo "Creating SQLite DB file..."
  touch "$DB_DATABASE"
fi

# Fix permissions for SQLite + Laravel runtime directories
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache "$(dirname "$DB_DATABASE")"
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache "$(dirname "$DB_DATABASE")"

# If APP_KEY missing, generate one
if [ -z "$APP_KEY" ]; then
  echo "APP_KEY missing — generating one..."
  php artisan key:generate --force
fi

echo "Running migrations..."
php artisan migrate --force || echo "Migration step failed"

echo "Running seeders..."
php artisan db:seed --force || echo "Database seeding skipped or failed"

echo "Starting Apache..."
exec "$@"
