<?php

namespace App\Models;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use BelongsToMerchant, HasFactory, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'name',
        'email',
        'phone',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    // Relationships
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function apiCredentials()
    {
        return $this->hasMany(ApiCredential::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active');
    }
}
