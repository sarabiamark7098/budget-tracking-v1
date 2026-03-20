# Ensure folders are writable for Laravel
chmod -R 775 storage bootstrap/cache

# Production optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations and seeders (The part you specifically asked for)
php artisan migrate --force --seed

# Start the application
php artisan serve --host=0.0.0.0 --port=$PORT
