<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use BelongsToMerchant, HasFactory, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'name',
        'slug',
        'base_price_cents',
        'currency',
        'billing_cycle',
        'included_units',
        'overage_rate_cents',
        'max_subscribers',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'billing_cycle' => BillingCycle::class,
            'base_price_cents' => 'integer',
            'included_units' => 'integer',
            'overage_rate_cents' => 'integer',
            'max_subscribers' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function activeSubscriptionsCount(): int
    {
        return $this->subscriptions()->where('status', 'active')->count();
    }

    // True once active subscriptions reach 90% of max_subscribers. Always
    // false when max_subscribers is null (unlimited).
    public function isNearSubscriberLimit(): bool
    {
        if ($this->max_subscribers === null) {
            return false;
        }

        return $this->activeSubscriptionsCount() >= (int) ceil($this->max_subscribers * 0.9);
    }
}
