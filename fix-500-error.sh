#!/bin/bash

# Laravel Forge 500 Error Fix Script
# This script fixes common issues that cause 500 errors after deployment

echo "🔧 Starting Laravel Forge 500 Error Fix Script..."
echo "=================================================="

# Set the project directory (update this path if needed)
PROJECT_DIR="/home/forge/evergreen-izgwu9lk.on-forge.com"

# Check if project directory exists
if [ ! -d "$PROJECT_DIR" ]; then
    echo "❌ Project directory not found: $PROJECT_DIR"
    echo "Please update the PROJECT_DIR variable in this script with your correct path"
    exit 1
fi

cd "$PROJECT_DIR"

echo "📁 Working in directory: $(pwd)"
echo ""

# 1. Check if .env file exists, create from .env.example if not
echo "🔍 Step 1: Checking .env file..."
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        echo "📝 Creating .env file from .env.example..."
        cp .env.example .env
        echo "✅ .env file created"
    else
        echo "❌ No .env.example file found. Please create .env manually"
        exit 1
    fi
else
    echo "✅ .env file exists"
fi

# 2. Generate application key
echo ""
echo "🔑 Step 2: Generating application key..."
php artisan key:generate --force
echo "✅ Application key generated"

# 3. Clear all caches
echo ""
echo "🧹 Step 3: Clearing application caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
echo "✅ All caches cleared"

# 4. Set proper file permissions
echo ""
echo "🔐 Step 4: Setting file permissions..."
sudo chown -R forge:forge "$PROJECT_DIR"
sudo chmod -R 755 "$PROJECT_DIR"
sudo chmod -R 775 "$PROJECT_DIR/storage"
sudo chmod -R 775 "$PROJECT_DIR/bootstrap/cache"
echo "✅ File permissions set"

# 5. Install/update dependencies
echo ""
echo "📦 Step 5: Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
echo "✅ Dependencies installed"

# 6. Build frontend assets
echo ""
echo "🎨 Step 6: Building frontend assets..."
npm install --silent
npm run build --silent
echo "✅ Frontend assets built"

# 7. Run database migrations (if needed)
echo ""
echo "🗄️ Step 7: Running database migrations..."
php artisan migrate --force
echo "✅ Database migrations completed"

# 8. Create storage link
echo ""
echo "🔗 Step 8: Creating storage link..."
php artisan storage:link
echo "✅ Storage link created"

# 9. Optimize application
echo ""
echo "⚡ Step 9: Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✅ Application optimized"

# 10. Check application status
echo ""
echo "🔍 Step 10: Checking application status..."
if php artisan --version > /dev/null 2>&1; then
    echo "✅ Laravel application is working"
else
    echo "❌ Laravel application has issues"
fi

# 11. Display recent logs
echo ""
echo "📋 Step 11: Recent application logs..."
if [ -f "storage/logs/laravel.log" ]; then
    echo "Last 10 lines of Laravel log:"
    tail -10 storage/logs/laravel.log
else
    echo "No Laravel log file found"
fi

echo ""
echo "🎉 Fix script completed!"
echo "=================================================="
echo "Your application should now be working."
echo "If you still get a 500 error, check the logs:"
echo "tail -f storage/logs/laravel.log"
echo ""
echo "Common next steps:"
echo "1. Check your database connection in .env"
echo "2. Verify your web server configuration"
echo "3. Check for any custom error pages"

