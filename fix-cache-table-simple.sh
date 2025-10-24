#!/bin/bash

# Simple Cache Table Fix Script
# This script creates the cache table using a simpler approach

echo "🔧 Simple Cache Table Fix..."
echo "============================"

# Set the project directory
PROJECT_DIR="/home/forge/evergreen-izgwu9lk.on-forge.com"

cd "$PROJECT_DIR/current"

echo "📁 Working in directory: $(pwd)"
echo ""

# 1. Create cache table using a PHP file
echo "🗄️ Step 1: Creating cache table using PHP file..."
cat > create_cache_table.php << 'EOF'
<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    DB::statement("
        CREATE TABLE IF NOT EXISTS cache (
            `key` varchar(255) NOT NULL,
            `value` mediumtext NOT NULL,
            `expiration` int(11) NOT NULL,
            PRIMARY KEY (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Cache table created successfully\n";
} catch (Exception $e) {
    echo "❌ Error creating cache table: " . $e->getMessage() . "\n";
}
EOF

php create_cache_table.php
rm create_cache_table.php

# 2. Verify cache table exists
echo ""
echo "🔍 Step 2: Verifying cache table exists..."
php artisan tinker --execute="echo 'Cache table exists: ' . (Schema::hasTable('cache') ? 'YES' : 'NO');"

# 3. Test cache functionality
echo ""
echo "🧪 Step 3: Testing cache functionality..."
php artisan tinker --execute="
try {
    Cache::put('test_key', 'test_value', 60);
    \$value = Cache::get('test_key');
    if (\$value === 'test_value') {
        echo '✅ Cache is working correctly';
    } else {
        echo '❌ Cache test failed';
    }
} catch (Exception \$e) {
    echo '❌ Cache test error: ' . \$e->getMessage();
}
"

# 4. Clear caches
echo ""
echo "🧹 Step 4: Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✅ Caches cleared"

echo ""
echo "🎉 Simple cache table fix completed!"
echo "===================================="
