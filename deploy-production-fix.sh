#!/bin/bash

# Production Deployment Fix Script
# Run this on your production server via Laravel Forge

echo "🚀 Starting Production Deployment Fix..."

# 1. Pull latest changes
echo "📥 Pulling latest changes from GitHub..."
git pull origin master

# 2. Install/Update dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# 3. Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 4. Run seeders to ensure admin user and permissions exist
echo "🌱 Running database seeders..."
php artisan db:seed --class=UnifiedProductionSeeder --force

# 5. Clear all caches
echo "🧹 Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 6. Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Set proper permissions
echo "🔐 Setting file permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache

echo "✅ Production deployment fix completed!"
echo "🎉 Your production site should now match your local environment."
