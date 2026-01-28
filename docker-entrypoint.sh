#!/bin/bash
set -e

if [ ! -f .env ] && [ -f .env.example ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
fi

echo "Installing dependencies..."
composer install --no-interaction

echo "Waiting for database to be ready..."

# Wait for MySQL to be fully ready (healthcheck only checks if mysqld is running, not if it accepts connections)
until php -r "new PDO('mysql:host=db;port=3306;dbname=fuel_api', 'root', 'secret');" 2>/dev/null; do
    echo "Database not ready yet, waiting..."
    sleep 2
done

echo "Database is ready!"

# Run setup script to create tables
echo "Running database setup..."
php bin/setup.php

echo "Starting PHP development server..."
exec php -S 0.0.0.0:8000 -t public public/router.php
