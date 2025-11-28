<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Loggable;

class InvoiceItem extends Model
{
    use HasFactory, Loggable;

    protected $fillable = [
        'billing_invoice_id',
        'description',
        'quantity',
        'unit_price',
        'total',
        'expense_category_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Auto-calculate total if not set
            if (empty($item->total) || $item->isDirty(['quantity', 'unit_price'])) {
                $item->total = $item->quantity * $item->unit_price;
            }
        });
    }

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(BillingInvoice::class, 'billing_invoice_id');
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}

