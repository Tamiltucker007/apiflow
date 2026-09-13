<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use BelongsToMerchant;

    protected $fillable = [
        'merchant_id',
        'customer_id',
        'subscription_id',
        'invoice_number',
        'period_start',
        'period_end',
        'base_amount_cents',
        'overage_units',
        'overage_amount_cents',
        'total_amount_cents',
        'currency',
        'status',
        'issued_at',
        'paid_at',
        'stripe_payment_intent_id',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'base_amount_cents' => 'integer',
            'overage_units' => 'integer',
            'overage_amount_cents' => 'integer',
            'total_amount_cents' => 'integer',
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
