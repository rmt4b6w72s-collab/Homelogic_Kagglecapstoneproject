#!/bin/bash

# Fix Remaining Issues Script
# This script fixes the cache table and symlink issues

echo "🔧 Fixing Remaining Issues..."
echo "============================="

# Set the project directory
PROJECT_DIR="/home/forge/evergreen-izgwu9lk.on-forge.com"

cd "$PROJECT_DIR"

echo "📁 Working in directory: $(pwd)"
echo ""

# 1. Create the missing cache table
echo "🗄️ Step 1: Creating missing cache table..."
cd releases/$(ls -t releases/ | head -1)
php artisan cache:table
php artisan migrate --force
echo "✅ Cache table created"

# 2. Create the current symlink
echo ""
echo "🔗 Step 2: Creating current symlink..."
cd "$PROJECT_DIR"
LATEST_RELEASE=$(ls -t releases/ | head -1)
if [ -n "$LATEST_RELEASE" ]; then
    # Remove existing symlink if it exists
    rm -f current
    # Create new symlink
    ln -s "releases/$LATEST_RELEASE" current
    echo "✅ Current symlink created pointing to: releases/$LATEST_RELEASE"
else
    echo "❌ No releases found"
fi

# 3. Set proper permissions
echo ""
echo "🔐 Step 3: Setting final permissions..."
chown -R forge:forge . 2>/dev/null || echo "⚠️ Could not change ownership"
chmod -R 755 . 2>/dev/null || echo "⚠️ Could not change permissions"
chmod -R 775 current/storage 2>/dev/null || echo "⚠️ Could not change storage permissions"
chmod -R 775 current/bootstrap/cache 2>/dev/null || echo "⚠️ Could not change bootstrap/cache permissions"
echo "✅ Permissions set"

# 4. Final cache clear
echo ""
echo "🧹 Step 4: Final cache clear..."
cd current
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✅ Final cache clear completed"

# 5. Check application status
echo ""
echo "🔍 Step 5: Final application check..."
if php artisan --version > /dev/null 2>&1; then
    echo "✅ Laravel application is working"
    php artisan --version
else
    echo "❌ Laravel application has issues"
fi

echo ""
echo "🎉 Fix completed!"
echo "============================="
echo "Your application should now be working properly."
echo "The current symlink has been created and the cache table exists."




