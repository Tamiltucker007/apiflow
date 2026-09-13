<?php

namespace App\Support;

class Money
{
    private const SYMBOLS = [
        'INR' => 'Rs',
    ];

    public static function format(int $cents, string $currency = 'INR'): string
    {
        $symbol = self::SYMBOLS[$currency] ?? $currency;

        return $symbol.' '.number_format($cents / 100, 2);
    }
}
