<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Loggable;
use App\Models\Scopes\FacilityScope;

class Medication extends Model
{
    use HasFactory, SoftDeletes, Loggable;

    protected static function booted()
    {
        static::addGlobalScope(new FacilityScope);
    }

    protected $fillable = [
        'resident_id',
        'branch_id',
        'drug_id',
        'name',
        'instructions',
        'quantity',
        'diagnosis',
        'created_by',
        'prescription_date',
        'start_date',
        'end_date',
        'notes',
        'is_active',
        'time_1',
        'time_2',
        'time_3',
        'time_4',
    ];

    protected $casts = [
        'prescription_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'quantity' => 'string', // Keep as string since it can be "30 tablets" etc
        'is_active' => 'boolean',
        'time_1' => 'string',
        'time_2' => 'string',
        'time_3' => 'string',
        'time_4' => 'string',
    ];

    /**
     * Serialize dates to YYYY-MM-DD format to prevent timezone shifts
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d');
    }

    // Mutators to ensure dates are stored correctly (as date-only, no time component)
    // This prevents timezone shifts when dates are sent as YYYY-MM-DD strings
    public function setStartDateAttribute($value)
    {
        if ($value) {
            // If it's already a date string in YYYY-MM-DD format, use it directly
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                // Store directly as string to avoid any Carbon timezone conversion
                $this->attributes['start_date'] = $value;
            } elseif ($value instanceof \Carbon\Carbon) {
                // If it's already a Carbon instance (from validation), we need to be careful
                // Carbon might have parsed it as UTC, so we need to get the date in app timezone
                // But we want to preserve the date components, not convert the time
                // So we format it in the app timezone to get the correct date
                $this->attributes['start_date'] = $value->setTimezone(config('app.timezone'))->format('Y-m-d');
            } else {
                // Parse the date string and extract date part only
                try {
                    // Try to extract YYYY-MM-DD from the string
                    $dateStr = is_string($value) ? substr($value, 0, 10) : (string)$value;
                    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $dateStr, $matches)) {
                        // If we can extract YYYY-MM-DD, use it directly
                        $this->attributes['start_date'] = $matches[1];
                    } else {
                        // Parse and format in app timezone
                        $parsed = \Carbon\Carbon::parse($value)->setTimezone(config('app.timezone'));
                        $this->attributes['start_date'] = $parsed->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    // Last resort: try to parse and format
                    $parsed = \Carbon\Carbon::parse($value)->setTimezone(config('app.timezone'));
                    $this->attributes['start_date'] = $parsed->format('Y-m-d');
                }
            }
        } else {
            $this->attributes['start_date'] = $value;
        }
    }

    public function setEndDateAttribute($value)
    {
        if ($value) {
            // If it's already a date string in YYYY-MM-DD format, use it directly
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                // Store directly as string to avoid any Carbon timezone conversion
                $this->attributes['end_date'] = $value;
            } elseif ($value instanceof \Carbon\Carbon) {
                // If it's already a Carbon instance (from validation), extract date components directly
                // Use the date in the app timezone without conversion
                $this->attributes['end_date'] = $value->setTimezone(config('app.timezone'))->format('Y-m-d');
            } else {
                // Parse the date string and extract date part only
                try {
                    // Try to extract YYYY-MM-DD from the string
                    $dateStr = is_string($value) ? substr($value, 0, 10) : (string)$value;
                    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $dateStr, $matches)) {
                        // If we can extract YYYY-MM-DD, use it directly
                        $this->attributes['end_date'] = $matches[1];
                    } else {
                        // Parse and format in app timezone
                        $parsed = \Carbon\Carbon::parse($value)->setTimezone(config('app.timezone'));
                        $this->attributes['end_date'] = $parsed->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    // Last resort: try to parse and format
                    $parsed = \Carbon\Carbon::parse($value)->setTimezone(config('app.timezone'));
                    $this->attributes['end_date'] = $parsed->format('Y-m-d');
                }
            }
        } else {
            $this->attributes['end_date'] = $value;
        }
    }

    public function setPrescriptionDateAttribute($value)
    {
        if ($value) {
            // If it's already a date string in YYYY-MM-DD format, use it directly
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                // Store directly as string to avoid any Carbon timezone conversion
                $this->attributes['prescription_date'] = $value;
            } elseif ($value instanceof \Carbon\Carbon) {
                // If it's already a Carbon instance (from validation), extract date components directly
                // Use the date in the app timezone without conversion
                $this->attributes['prescription_date'] = $value->setTimezone(config('app.timezone'))->format('Y-m-d');
            } else {
                // Parse the date string and extract date part only
                try {
                    // Try to extract YYYY-MM-DD from the string
                    $dateStr = is_string($value) ? substr($value, 0, 10) : (string)$value;
                    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $dateStr, $matches)) {
                        // If we can extract YYYY-MM-DD, use it directly
                        $this->attributes['prescription_date'] = $matches[1];
                    } else {
                        // Parse and format in app timezone
                        $parsed = \Carbon\Carbon::parse($value)->setTimezone(config('app.timezone'));
                        $this->attributes['prescription_date'] = $parsed->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    // Last resort: try to parse and format
                    $parsed = \Carbon\Carbon::parse($value)->setTimezone(config('app.timezone'));
                    $this->attributes['prescription_date'] = $parsed->format('Y-m-d');
                }
            }
        } else {
            $this->attributes['prescription_date'] = $value;
        }
    }

    // Relationships
    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function administrations()
    {
        return $this->hasMany(MedicationAdministration::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByResident($query, $residentId)
    {
        return $query->where('resident_id', $residentId);
    }


    // Accessors
    public function getInstructionDisplayAttribute()
    {
        $instructions = [
            't.i.d' => 'Thrice daily',
            'q.i.d' => 'Four times a day',
            'b.i.d' => 'Twice daily',
            'PRN' => 'As needed',
            'h.s' => 'Hour of sleep',
            'a.m' => 'Morning',
            'p.m' => 'Evening',
        ];

        return $instructions[$this->instructions] ?? $this->instructions;
    }

    // Accessors
    public function getNameAttribute()
    {
        // If name is null or empty, create a name from drug and resident
        if (empty($this->attributes['name'])) {
            $drugName = $this->drug ? $this->drug->name : 'Unknown Drug';
            $residentName = $this->resident ? $this->resident->name : 'Unknown Resident';
            return "{$drugName} - {$residentName}";
        }
        return $this->attributes['name'];
    }

    // Static method for instruction options
    public static function getInstructionOptions()
    {
        return [
            't.i.d' => 't.i.d — Thrice daily',
            'q.i.d' => 'q.i.d — Four times a day',
            'b.i.d' => 'b.i.d — Twice daily',
            'PRN' => 'PRN — As needed',
            'h.s' => 'h.s — Hour of sleep',
            'a.m' => 'a.m — Morning',
            'p.m' => 'p.m — Evening',
        ];
    }
}
