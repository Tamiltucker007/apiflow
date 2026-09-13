<?php

namespace App\Enums;

enum UserRole: string
{
    case MerchantAdmin = 'merchant_admin';
    case MerchantStaff = 'merchant_staff';

    public function label(): string
    {
        return match ($this) {
            self::MerchantAdmin => 'Merchant Admin',
            self::MerchantStaff => 'Merchant Staff',
        };
    }
}
