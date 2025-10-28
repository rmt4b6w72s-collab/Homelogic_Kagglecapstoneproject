#!/bin/bash

# Fresh Migration Script for Production
echo "🔄 Starting fresh migration..."

# Navigate to the application directory
cd /home/forge/evergreen-gpga9pd.on-forge.com

# Pull the latest code
echo "📥 Pulling latest code..."
git pull origin master

# Drop all tables and run fresh migrations
echo "🗄️ Running fresh migrations..."
php artisan migrate:fresh --force

# Seed the database
echo "🌱 Seeding database..."
php artisan db:seed --force

# Clear and cache configuration
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Clear application cache
php artisan cache:clear

# Restart PHP-FPM
echo "🔄 Restarting PHP-FPM..."
sudo service php8.3-fpm restart

echo "✅ Fresh migration completed successfully!"
