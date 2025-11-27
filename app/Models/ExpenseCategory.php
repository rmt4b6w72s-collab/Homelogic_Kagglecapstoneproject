<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Loggable;
use App\Models\Scopes\FacilityScope;

class ExpenseCategory extends Model
{
    use HasFactory, SoftDeletes, Loggable;

    protected static function booted()
    {
        static::addGlobalScope(new FacilityScope);
    }

    protected $fillable = [
        'facility_id',
        'name',
        'description',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Static methods
    public static function getTypeOptions()
    {
        return [
            'operational' => 'Operational',
            'resident_billing' => 'Resident Billing',
            'staff' => 'Staff',
            'vendor' => 'Vendor',
            'other' => 'Other',
        ];
    }
}

