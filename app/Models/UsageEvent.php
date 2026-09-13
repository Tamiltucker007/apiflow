<?php

namespace App\Models;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageEvent extends Model
{
    use BelongsToMerchant, HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'merchant_id',
        'customer_id',
        'subscription_id',
        'event_key',
        'units',
        'recorded_date',
        'metadata',
        'is_aggregated',
    ];

    protected function casts(): array
    {
        return [
            'units' => 'integer',
            'recorded_date' => 'date',
            'metadata' => 'array',
            'is_aggregated' => 'boolean',
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

    public function scopeUnaggregated($query)
    {
        return $query->where('is_aggregated', false);
    }
}
