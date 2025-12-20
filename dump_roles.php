<?php

use App\Models\Role;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$roles = \App\Models\Role::all();

echo "Roles in database:\n";
foreach ($roles as $role) {
    echo "- '{$role->name}' (ID: {$role->id})\n";
}

if ($roles->count() === 0) {
    echo "No roles found!\n";
}
