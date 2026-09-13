<?php

namespace App\Models;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Model;

class ApiCredential extends Model
{
    use BelongsToMerchant;

    protected $fillable = [
        'merchant_id',
        'customer_id',
        'key_prefix',
        'key_hash',
        'name',
        'is_active',
        'expires_at',
        'last_used_at',
    ];

    protected $hidden = [
        'key_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
