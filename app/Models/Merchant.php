<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Merchant extends Model
{
    use HasFactory, SoftDeletes;

    // Falls back to the platform's own indigo/fuchsia brand when a merchant
    // has no theme set (e.g. one created before theming existed), so every
    // caller can use themeFrom()/themeTo() unconditionally instead of
    // null-checking at every call site.
    private const DEFAULT_THEME_FROM = '#4f46e5';

    private const DEFAULT_THEME_TO = '#c026d3';

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'theme_from',
        'theme_to',
        'slug',
        'email',
        'phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function themeFrom(): string
    {
        return $this->theme_from ?? self::DEFAULT_THEME_FROM;
    }

    public function themeTo(): string
    {
        return $this->theme_to ?? self::DEFAULT_THEME_TO;
    }

    protected static function booted(): void
    {
        static::creating(function (Merchant $merchant) {
            $merchant->uuid ??= (string) \Illuminate\Support\Str::uuid();
        });
    }

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function plans()
    {
        return $this->hasMany(Plan::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
