#!/bin/bash

# Script to seed Facilities, Branches, and Residents in production
# Usage: Run this script in Laravel Forge or SSH into your server

echo "🌱 Seeding Facilities, Branches, and Residents..."
echo ""

echo "📋 Step 1/3: Seeding Facilities..."
php artisan db:seed --class=FacilitySeeder --force

echo ""
echo "📋 Step 2/3: Seeding Branches..."
php artisan db:seed --class=BranchSeeder --force

echo ""
echo "📋 Step 3/3: Seeding Residents..."
php artisan db:seed --class=ResidentSeeder --force

echo ""
echo "✅ All seeders completed successfully!"
echo ""

