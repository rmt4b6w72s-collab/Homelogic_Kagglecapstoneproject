#!/bin/bash

# Direct Cache Table Fix - Run this directly on Forge server
# This script creates the cache table without needing to download files

echo "🔧 Direct Cache Table Fix..."
echo "============================"

# Navigate to the current release directory
cd /home/forge/evergreen-izgwu9lk.on-forge.com/current

echo "📁 Working in directory: $(pwd)"
echo ""

# Create cache table using a simple PHP approach
echo "🗄️ Creating cache table..."
php -r "
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    DB::statement(\"
        CREATE TABLE IF NOT EXISTS cache (
            \`key\` varchar(255) NOT NULL,
            \`value\` mediumtext NOT NULL,
            \`expiration\` int(11) NOT NULL,
            PRIMARY KEY (\`key\`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    \");
    echo \"✅ Cache table created successfully\n\";
} catch (Exception \$e) {
    echo \"❌ Error: \" . \$e->getMessage() . \"\n\";
}
"

# Test cache functionality
echo ""
echo "🧪 Testing cache functionality..."
php -r "
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Cache;

try {
    Cache::put('test_key', 'test_value', 60);
    \$value = Cache::get('test_key');
    if (\$value === 'test_value') {
        echo \"✅ Cache is working correctly\n\";
    } else {
        echo \"❌ Cache test failed\n\";
    }
} catch (Exception \$e) {
    echo \"❌ Cache test error: \" . \$e->getMessage() . \"\n\";
}
"

# Clear caches
echo ""
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "🎉 Cache table fix completed!"
echo "============================"




