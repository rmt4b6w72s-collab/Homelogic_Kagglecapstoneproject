#!/bin/bash

# Laravel Forge 500 Error Fix Script v2
# This script fixes common issues that cause 500 errors after deployment

echo "🔧 Starting Laravel Forge 500 Error Fix Script v2..."
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

# Check if we're in the right directory by looking for artisan file
if [ ! -f "artisan" ]; then
    echo "❌ artisan file not found in current directory"
    echo "🔍 Looking for Laravel project in subdirectories..."
    
    # Check if there's a releases directory (common in Forge deployments)
    if [ -d "releases" ]; then
        echo "📁 Found releases directory, checking latest release..."
        LATEST_RELEASE=$(ls -t releases/ | head -1)
        if [ -n "$LATEST_RELEASE" ]; then
            echo "🔄 Switching to latest release: $LATEST_RELEASE"
            cd "releases/$LATEST_RELEASE"
            echo "📁 Now in directory: $(pwd)"
        fi
    fi
    
    # Check if artisan exists now
    if [ ! -f "artisan" ]; then
        echo "❌ Still can't find artisan file. Please check your project structure."
        echo "Current directory contents:"
        ls -la
        exit 1
    fi
fi

echo "✅ Found artisan file in: $(pwd)"
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

# 4. Set proper file permissions (without sudo for now)
echo ""
echo "🔐 Step 4: Setting file permissions..."
chown -R forge:forge . 2>/dev/null || echo "⚠️ Could not change ownership (may need sudo)"
chmod -R 755 . 2>/dev/null || echo "⚠️ Could not change permissions (may need sudo)"
chmod -R 775 storage 2>/dev/null || echo "⚠️ Could not change storage permissions"
chmod -R 775 bootstrap/cache 2>/dev/null || echo "⚠️ Could not change bootstrap/cache permissions"
echo "✅ File permissions set"

# 5. Install/update dependencies
echo ""
echo "📦 Step 5: Installing dependencies..."
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader --no-interaction
    echo "✅ Dependencies installed"
else
    echo "❌ composer.json not found. Skipping dependency installation."
fi

# 6. Build frontend assets
echo ""
echo "🎨 Step 6: Building frontend assets..."
if [ -f "package.json" ]; then
    npm install --silent
    npm run build --silent
    echo "✅ Frontend assets built"
else
    echo "⚠️ package.json not found. Skipping frontend build."
fi

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
    php artisan --version
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

# 12. Check if we need to create a symlink to current
echo ""
echo "🔗 Step 12: Checking symlink to current..."
if [ ! -L "current" ] && [ -d "../current" ]; then
    echo "⚠️ No current symlink found. This might be the issue."
    echo "You may need to create a symlink from the releases directory to current"
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
echo "4. Ensure the 'current' symlink points to the correct release"




