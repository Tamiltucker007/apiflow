<?php

namespace App\Models;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

// Extends Authenticatable (not plain Model) so a Customer can log into the
// self-service portal via the "customer" guard, separate from App\Models\User
// (merchant-side dashboard logins) — see config/auth.php and routes/customer-portal.php.
class Customer extends Authenticatable
{
    use BelongsToMerchant, HasFactory, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'name',
        'email',
        'phone',
        'password',
        'metadata',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'metadata' => 'array',
            'is_active' => 'boolean',
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

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active');
    }
}
