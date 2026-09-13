<?php

namespace App\Enums;

enum InvoiceItemType: string
{
    case BaseCharge = 'base_charge';
    case Overage = 'overage';
    case ProrationCredit = 'proration_credit';
    case ProrationCharge = 'proration_charge';
}
