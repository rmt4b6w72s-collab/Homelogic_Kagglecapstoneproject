<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Support\Facades\Storage;
use App\Models\Notification;
use App\Traits\Loggable;
use App\Traits\FormatsPhoneNumbers;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, Loggable, FormatsPhoneNumbers;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'profile_image',
        'first_name',
        'middle_names',
        'last_name',
        'phone_number',
        'date_of_birth',
        'marital_status',
        'sex',
        'position',
        'credentials',
        'credential_details',
        'date_employed',
        'supervisor_name',
        'provider_name',
        'role',
        'assigned_branch_id',
        'is_active',
        'hire_date',
        'notes',
        'password',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['profile_image_url', 'is_caregiver'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'date_employed' => 'date',
            'hire_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if user can access Filament panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Only allow users with view_admin_panel permission
        return $this->is_active && $this->hasPermission('view_admin_panel');
    }

    /**
     * Get the full URL for the profile image
     */
    public function getProfileImageUrlAttribute()
    {
        // Use null coalescing with explicit parentheses to avoid PHP notices
        // when the key isn't selected in lightweight queries (e.g., select id,name).
        $raw = ($this->attributes['profile_image'] ?? null);
        if (!$raw) {
            return null;
        }

        $value = $raw;

        // If already a full URL, return as is
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        // Return the storage URL
        return Storage::disk('public')->url($value);
    }

    // Relationships
    public function notifications()
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function unreadNotifications()
    {
        return $this->hasMany(Notification::class)->where('is_read', false)->latest();
    }

    public function assignedBranch()
    {
        return $this->belongsTo(Branch::class, 'assigned_branch_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'caregiver_id');
    }

    public function activeAssignments()
    {
        return $this->assignments()->active();
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'staff_id');
    }

    public function approvedLeaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'approved_by');
    }

    public function vitalSigns()
    {
        return $this->hasMany(VitalSign::class, 'taken_by');
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'assessor_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'created_by');
    }

    public function roles()
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles');
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function assignRole(string $role): void
    {
        $roleModel = Role::where('name', $role)->first();
        if ($roleModel) {
            $this->roles()->syncWithoutDetaching([$roleModel->id]);
        }
    }

    public function removeRole(string $role): void
    {
        $roleModel = Role::where('name', $role)->first();
        if ($roleModel) {
            $this->roles()->detach($roleModel->id);
        }
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()->whereHas('permissions', function ($query) use ($permission) {
            $query->where('name', $permission);
        })->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    // Scopes
    public function scopeCaregivers($query)
    {
        return $query->where('role', 'caregiver');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('assigned_branch_id', $branchId);
    }

    // Boot method to automatically set name field
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($user) {
            if (empty($user->name)) {
                $user->name = trim(implode(' ', array_filter([
                    $user->first_name,
                    $user->middle_names,
                    $user->last_name
                ]))) ?: $user->email;
            }
        });
        
        static::updating(function ($user) {
            if ($user->isDirty(['first_name', 'middle_names', 'last_name'])) {
                $user->name = trim(implode(' ', array_filter([
                    $user->first_name,
                    $user->middle_names,
                    $user->last_name
                ]))) ?: $user->email;
            }
        });
    }

    // Accessors
    public function getFullNameAttribute()
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_names,
            $this->last_name
        ]);
        return trim(implode(' ', $parts));
    }

    public function getNameAttribute()
    {
        return $this->attributes['name'] ?? $this->full_name ?: $this->email;
    }

    public function getIsCaregiverAttribute(): bool
    {
        $roleValue = $this->role ? strtolower(trim($this->role)) : null;
        if ($roleValue) {
            $normalized = str_replace([' ', '_'], '', $roleValue);
            if ($normalized === 'caregiver') {
                return true;
            }
        }

        if ($this->position && strcasecmp(trim($this->position), 'caregiver') === 0) {
            return true;
        }

        if (method_exists($this, 'hasAnyRole')) {
            if ($this->hasAnyRole(['caregiver', 'care_giver', 'Care Giver'])) {
                return true;
            }
        }

        return false;
    }

    // Get pending leave requests count for notifications
    public function getPendingLeaveRequestsCountAttribute(): int
    {
        return $this->leaveRequests()->where('status', 'pending')->count();
    }

    // Static methods for dropdown options
    public static function getMaritalStatusOptions()
    {
        return [
            'single' => 'Single',
            'married' => 'Married',
            'divorced' => 'Divorced',
            'widowed' => 'Widowed',
            'separated' => 'Separated',
        ];
    }

    public static function getPositionOptions()
    {
        return [
            'caregiver' => 'Caregiver',
            'nurse' => 'Nurse',
            'supervisor' => 'Supervisor',
            'administrator' => 'Administrator',
            'manager' => 'Manager',
            'support_staff' => 'Support Staff',
        ];
    }

    public static function getRoleOptions()
    {
        return [
            'care_giver' => 'Care Giver',
            'registered_nurse' => 'Registered Nurse',
            'licensed_nurse' => 'Licensed Nurse',
            'administrator' => 'Administrator',
            'manager' => 'Manager',
            'support_staff' => 'Support Staff',
        ];
    }

    public static function getSexOptions()
    {
        return [
            'male' => 'Male',
            'female' => 'Female',
            'other' => 'Other',
        ];
    }

    protected function phoneNumber(): Attribute
    {
        return $this->phoneAttribute();
    }
}
