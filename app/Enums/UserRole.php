<?php

namespace App\Enums;

// A single case, not a boolean/removed column: every merchant-side dashboard
// user is a full admin (no read-only "staff" tier — removed after review
// confirmed the brief itself never asked for role differentiation, and
// merchant business data has one owner-level user type). Kept as an enum
// rather than deleting the `role` column so the door stays open if a
// lighter-weight teammate tier is ever needed again, without a schema change.
enum UserRole: string
{
    case MerchantAdmin = 'merchant_admin';

    public function label(): string
    {
        return match ($this) {
            self::MerchantAdmin => 'Merchant Admin',
        };
    }
}
