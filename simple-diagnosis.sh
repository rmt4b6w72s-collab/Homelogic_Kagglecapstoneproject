#!/bin/bash

# Simple Diagnosis Script
# This script performs basic checks to identify the 500 error cause

echo "🔍 Simple 500 Error Diagnosis..."
echo "================================="

# Navigate to the current release directory
cd /home/forge/evergreen-izgwu9lk.on-forge.com/current

echo "📁 Working in directory: $(pwd)"
echo ""

# 1. Basic directory check
echo "🔍 Step 1: Basic directory check..."
if [ -f "artisan" ]; then
    echo "✅ Found artisan file"
else
    echo "❌ No artisan file found - wrong directory"
    exit 1
fi

# 2. Check .env file
echo ""
echo "🔍 Step 2: Checking .env file..."
if [ -f ".env" ]; then
    echo "✅ .env file exists"
    echo "Key values:"
    grep -E "^(APP_KEY|DB_CONNECTION|DB_HOST|DB_DATABASE)" .env
else
    echo "❌ .env file missing"
fi

# 3. Check Laravel version
echo ""
echo "🔍 Step 3: Laravel version check..."
php artisan --version

# 4. Test database connection
echo ""
echo "🔍 Step 4: Database connection test..."
php artisan tinker --execute="
try {
    DB::connection()->getPdo();
    echo '✅ Database connection successful';
} catch (Exception \$e) {
    echo '❌ Database connection failed: ' . \$e->getMessage();
}
"

# 5. Check if cache table exists
echo ""
echo "🔍 Step 5: Cache table check..."
php artisan tinker --execute="
use Illuminate\Support\Facades\Schema;
if (Schema::hasTable('cache')) {
    echo '✅ Cache table exists';
} else {
    echo '❌ Cache table missing';
}
"

# 6. Check recent logs
echo ""
echo "🔍 Step 6: Recent application logs..."
if [ -f "storage/logs/laravel.log" ]; then
    echo "Last 5 lines of Laravel log:"
    tail -5 storage/logs/laravel.log
else
    echo "No Laravel log file found"
fi

# 7. Check file permissions
echo ""
echo "🔍 Step 7: File permissions check..."
ls -la storage/ | head -3
ls -la bootstrap/cache/ | head -3

# 8. Test a simple PHP execution
echo ""
echo "🔍 Step 8: PHP execution test..."
php -r "echo '✅ PHP is working\n';"

# 9. Check if we can bootstrap Laravel
echo ""
echo "🔍 Step 9: Laravel bootstrap test..."
php -r "
try {
    require_once 'vendor/autoload.php';
    \$app = require_once 'bootstrap/app.php';
    echo '✅ Laravel bootstrap successful';
} catch (Exception \$e) {
    echo '❌ Laravel bootstrap failed: ' . \$e->getMessage();
}
"

echo ""
echo "🎉 Simple diagnosis completed!"
echo "=============================="
