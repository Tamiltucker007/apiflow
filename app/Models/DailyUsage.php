<?php

namespace App\Models;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Model;

class DailyUsage extends Model
{
    use BelongsToMerchant;

    protected $table = 'daily_usage';

    protected $fillable = [
        'merchant_id',
        'customer_id',
        'subscription_id',
        'usage_date',
        'total_units',
    ];

    protected function casts(): array
    {
        return [
            'usage_date' => 'date',
            'total_units' => 'integer',
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

    public function scopeForPeriod($query, $start, $end)
    {
        return $query->whereBetween('usage_date', [$start, $end]);
    }
}
