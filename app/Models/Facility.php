<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Loggable;

class Facility extends Model
{
    use HasFactory, SoftDeletes, Loggable;

    protected $fillable = [
        'name',
        'location',
        'description',
        'address',
        'phone',
        'email',
        'brochure_url',
        'brochure_color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getBranchCountAttribute()
    {
        return $this->branches()->count();
    }
}
