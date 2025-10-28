#!/bin/bash

# Fix Cache Table Script
# This script manually creates the cache table in the database

echo "🔧 Fixing Cache Table Issue..."
echo "=============================="

# Set the project directory
PROJECT_DIR="/home/forge/evergreen-izgwu9lk.on-forge.com"

cd "$PROJECT_DIR/current"

echo "📁 Working in directory: $(pwd)"
echo ""

# 1. Check if cache table exists
echo "🔍 Step 1: Checking if cache table exists..."
if php artisan tinker --execute="echo 'Cache table exists: ' . (Schema::hasTable('cache') ? 'YES' : 'NO');" 2>/dev/null | grep -q "YES"; then
    echo "✅ Cache table already exists"
    exit 0
else
    echo "❌ Cache table does not exist"
fi

# 2. Create cache table manually using raw SQL
echo ""
echo "🗄️ Step 2: Creating cache table manually..."
php artisan tinker --execute="
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

try {
    DB::statement('CREATE TABLE IF NOT EXISTS cache (
        `key` varchar(255) NOT NULL,
        `value` mediumtext NOT NULL,
        `expiration` int(11) NOT NULL,
        PRIMARY KEY (`key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
    echo 'Cache table created successfully';
} catch (Exception \$e) {
    echo 'Error creating cache table: ' . \$e->getMessage();
}
"
echo "✅ Cache table creation attempted"

# 3. Verify cache table exists
echo ""
echo "🔍 Step 3: Verifying cache table exists..."
if php artisan tinker --execute="echo 'Cache table exists: ' . (Schema::hasTable('cache') ? 'YES' : 'NO');" 2>/dev/null | grep -q "YES"; then
    echo "✅ Cache table now exists"
else
    echo "❌ Cache table still does not exist"
    echo "🔧 Trying alternative method..."
    
    # Alternative: Use Laravel's cache:table command with force
    php artisan cache:table --force
    php artisan migrate --force
fi

# 4. Test cache functionality
echo ""
echo "🧪 Step 4: Testing cache functionality..."
php artisan tinker --execute="
try {
    Cache::put('test_key', 'test_value', 60);
    \$value = Cache::get('test_key');
    if (\$value === 'test_value') {
        echo 'Cache is working correctly';
    } else {
        echo 'Cache test failed';
    }
} catch (Exception \$e) {
    echo 'Cache test error: ' . \$e->getMessage();
}
"

# 5. Clear caches one more time
echo ""
echo "🧹 Step 5: Final cache clear..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo "✅ Final cache clear completed"

echo ""
echo "🎉 Cache table fix completed!"
echo "=============================="
echo "The cache table should now be working properly."




