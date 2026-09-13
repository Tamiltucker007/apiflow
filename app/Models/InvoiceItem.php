<?php

namespace App\Models;

use App\Enums\InvoiceItemType;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'description',
        'type',
        'plan_id',
        'units',
        'unit_price_cents',
        'amount_cents',
        'period_start',
        'period_end',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvoiceItemType::class,
            'units' => 'integer',
            'unit_price_cents' => 'integer',
            'amount_cents' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
