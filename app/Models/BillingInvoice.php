<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Loggable;
use App\Models\Scopes\FacilityScope;
use Carbon\Carbon;

class BillingInvoice extends Model
{
    use HasFactory, SoftDeletes, Loggable;

    protected static function booted()
    {
        static::addGlobalScope(new FacilityScope);
    }

    protected $fillable = [
        'facility_id',
        'branch_id',
        'resident_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'status',
        'payment_date',
        'payment_method',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = $invoice->generateInvoiceNumber();
            }
        });
    }

    // Relationships
    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('status', 'sent')
                  ->where('due_date', '<', now());
            });
    }

    public function scopeByResident($query, $residentId)
    {
        return $query->where('resident_id', $residentId);
    }

    // Methods
    public function calculateTotal()
    {
        $subtotal = $this->items()->sum('total');
        $this->subtotal = $subtotal;
        $this->total_amount = $subtotal + $this->tax_amount - $this->discount_amount;
        return $this->total_amount;
    }

    public function markAsPaid($paymentDate = null, $paymentMethod = null)
    {
        $this->update([
            'status' => 'paid',
            'payment_date' => $paymentDate ?? now(),
            'payment_method' => $paymentMethod,
        ]);
    }

    public function generateInvoiceNumber()
    {
        $facility = $this->facility ?? app('facility');
        $prefix = $facility ? strtoupper(substr($facility->name, 0, 3)) : 'INV';
        $year = now()->format('Y');
        $month = now()->format('m');
        
        $lastInvoice = static::where('invoice_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $newNumber);
    }

    public function isOverdue()
    {
        return $this->status !== 'paid' && $this->status !== 'cancelled' && $this->due_date < now();
    }

    // Static methods
    public static function getStatusOptions()
    {
        return [
            'draft' => 'Draft',
            'sent' => 'Sent',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
        ];
    }
}

