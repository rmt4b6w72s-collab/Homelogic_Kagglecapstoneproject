<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Loggable;

class PaymentNotificationPreference extends Model
{
    use HasFactory, Loggable;

    protected $fillable = [
        'facility_id',
        'user_id',
        'days_before_due',
        'notify_on_due_date',
        'notify_on_overdue',
        'overdue_reminder_interval_days',
        'email_enabled',
        'in_app_enabled',
        'notification_channels',
    ];

    protected $casts = [
        'days_before_due' => 'integer',
        'notify_on_due_date' => 'boolean',
        'notify_on_overdue' => 'boolean',
        'overdue_reminder_interval_days' => 'integer',
        'email_enabled' => 'boolean',
        'in_app_enabled' => 'boolean',
        'notification_channels' => 'array',
    ];

    // Relationships
    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForFacility($query, $facilityId)
    {
        return $query->where('facility_id', $facilityId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeGlobal($query, $facilityId)
    {
        return $query->where('facility_id', $facilityId)
            ->whereNull('user_id');
    }

    // Static methods
    public static function getDefaultPreferences()
    {
        return [
            'days_before_due' => 7,
            'notify_on_due_date' => true,
            'notify_on_overdue' => true,
            'overdue_reminder_interval_days' => 7,
            'email_enabled' => true,
            'in_app_enabled' => true,
            'notification_channels' => [],
        ];
    }
}

