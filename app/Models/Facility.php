<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Loggable;
use App\Traits\FormatsPhoneNumbers;

class Facility extends Model
{
    use HasFactory, SoftDeletes, Loggable;
    use FormatsPhoneNumbers;

    protected $fillable = [
        'name',
        'location',
        'description',
        'address',
        'phone',
        'email',
        'brochure_url',
        'brochure_color',
        'logo',
        'primary_color',
        'secondary_color',
        'accent_color',
        'subdomain',
        'registration_status',
        'registered_by_user_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['logo_url', 'branding'];

    // Relationships
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function registrations()
    {
        return $this->hasMany(FacilityRegistration::class, 'facility_name', 'name');
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

    protected function phone(): Attribute
    {
        return $this->phoneAttribute();
    }

    // Accessors
    public function getLogoUrlAttribute()
    {
        if (!$this->logo) {
            return null;
        }

        // If already a full URL, return as is
        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo;
        }

        // Return the storage URL
        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo);
    }

    public function getBrandingAttribute()
    {
        return [
            'logo' => $this->logo_url,
            'primary_color' => $this->primary_color ?? '#667eea',
            'secondary_color' => $this->secondary_color ?? '#764ba2',
            'accent_color' => $this->accent_color ?? '#f093fb',
            'name' => $this->name,
        ];
    }
}
