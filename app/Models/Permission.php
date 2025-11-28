<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Schema;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'guard_name',
        'group',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }

    public static function createPermission(string $name, string $group = null, string $description = null, string $guardName = 'web'): self
    {
        return static::create([
            'name' => $name,
            'guard_name' => $guardName,
            'group' => $group,
            'description' => $description,
        ]);
    }

    public static function getPermissionsByGroup(): array
    {
        $query = static::query();
        
        // Check if 'group' column exists before ordering by it
        if (Schema::hasColumn('permissions', 'group')) {
            $query->orderBy('group');
        }
        
        return $query->orderBy('name')->get()->groupBy(function ($permission) {
            // Use group if column exists, otherwise use 'Other'
            return Schema::hasColumn('permissions', 'group') && $permission->group 
                ? $permission->group 
                : 'Other';
        })->toArray();
    }
}
