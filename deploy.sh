#!/bin/bash
# Simple deploy script for production server
# Usage: ./deploy.sh

set -e

echo "Pulling latest changes..."
git pull

echo "Installing composer dependencies..."
composer install --no-dev --optimize-autoloader

echo "Running migrations..."
php artisan migrate --force

echo "Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "Generating API docs..."
php artisan l5-swagger:generate

echo "Setting permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "Caching for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Done!"
