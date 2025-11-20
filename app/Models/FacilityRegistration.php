<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityRegistration extends Model
{
    use HasFactory;

    protected $table = 'facility_registrations';

    protected $fillable = [
        'facility_name',
        'contact_name',
        'email',
        'phone',
        'address',
        'requested_subdomain',
        'status',
        'notes',
        'approved_by_user_id',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    // Relationships
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
