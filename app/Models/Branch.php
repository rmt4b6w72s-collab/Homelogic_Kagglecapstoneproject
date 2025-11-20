<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Loggable;
use App\Traits\FormatsPhoneNumbers;
use App\Models\Scopes\FacilityScope;

class Branch extends Model
{
    use HasFactory, SoftDeletes, Loggable;
    use FormatsPhoneNumbers;

    protected static function booted()
    {
        static::addGlobalScope(new FacilityScope);
    }

    protected $fillable = [
        'name',
        'address',
        'facility_id',
        'phone',
        'email',
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

    public function caregivers()
    {
        return $this->hasMany(User::class, 'assigned_branch_id');
    }

    public function residents()
    {
        return $this->hasMany(Resident::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getCaregiverCountAttribute()
    {
        return $this->caregivers()->count();
    }

    public function getResidentCountAttribute()
    {
        return $this->residents()->count();
    }

    protected function phone(): Attribute
    {
        return $this->phoneAttribute();
    }
}