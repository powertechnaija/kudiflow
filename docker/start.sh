#!/bin/sh

# If .env does not exist, create a minimal one
if [ ! -f .env ]; then
    echo "Creating .env file..."

    if [ -f .env.example ]; then
        cp .env.example .env
    else
        echo "APP_NAME=Laravel" > .env
        echo "APP_ENV=production" >> .env
        echo "APP_KEY=" >> .env
        echo "APP_DEBUG=false" >> .env
        echo "APP_URL=http://localhost" >> .env

        echo "LOG_CHANNEL=stack" >> .env
        echo "LOG_LEVEL=debug" >> .env

        echo "DB_CONNECTION=sqlite" >> .env
        echo "DB_DATABASE=/app/database/database.sqlite" >> .env
    fi
fi

# Set app key if missing
if ! grep -q "APP_KEY=base64:" .env; then
    php artisan key:generate --force
fi

# Ensure SQLite database exists
if [ ! -f /app/database/database.sqlite ]; then
    echo "Creating SQLite database..."
    mkdir -p /app/database
    touch /app/database/database.sqlite
    chmod 777 /app/database/database.sqlite
fi

# Run migrations & seed
echo "Running migrations..."
php artisan migrate --force || true

echo "Running seeders..."
php artisan db:seed --force || true

# Start Laravel server
php artisan serve --host=0.0.0.0 --port=8000
