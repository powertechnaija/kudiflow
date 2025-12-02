#!/bin/sh

# Load environment
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Generate app key if not set
php artisan key:generate --force

# Ensure SQLite database exists
if [ ! -f /app/database/database.sqlite ]; then
    touch /app/database/database.sqlite
    chmod 777 /app/database/database.sqlite
fi

# Run migrations + seed
php artisan migrate --force
php artisan db:seed --force

# Start Laravel server
php artisan serve --host=0.0.0.0 --port=8000
