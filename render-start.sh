#!/bin/bash

# Render.com deployment configuration

# Install dependencies
composer install --no-dev --optimize-autoloader --no-scripts

# Build assets if node_modules exists
if [ -f package.json ]; then
    npm install
    npm run build
fi

# Clear and optimize caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
php artisan migrate --force

# Start Laravel with Apache
exec vendor/bin/heroku-php-apache2 public/
