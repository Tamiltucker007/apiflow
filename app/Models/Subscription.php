<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use BelongsToMerchant, HasFactory;

    protected $fillable = [
        'merchant_id',
        'customer_id',
        'plan_id',
        'status',
        'current_period_start',
        'current_period_end',
        'started_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'current_period_start' => 'date',
            'current_period_end' => 'date',
            'started_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function planChanges()
    {
        return $this->hasMany(SubscriptionPlanChange::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::Active);
    }
}
