<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
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
        return true;
    }

    // Relationships
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

    public function getIsCaregiverAttribute()
    {
        return $this->role === 'caregiver' || $this->position === 'Caregiver';
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
}
