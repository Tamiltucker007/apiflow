<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case MerchantAdmin = 'merchant_admin';
    case MerchantStaff = 'merchant_staff';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::MerchantAdmin => 'Merchant Admin',
            self::MerchantStaff => 'Merchant Staff',
        };
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }
}
