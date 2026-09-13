<?php

namespace App\Enums;

use Carbon\Carbon;

enum BillingCycle: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    /**
     * Number of calendar months in one cycle.
     */
    public function months(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Quarterly => 3,
            self::Yearly => 12,
        };
    }

    /**
     * End of the calendar month/quarter/year containing $date. Periods are
     * calendar-aligned (not anniversary-based), so a subscription starting
     * mid-month gets a short first period ending at month-end — this is
     * what makes "prorate a mid-cycle start" meaningful; the next period
     * (see SubscriptionService::advanceToNextPeriod) is a full one.
     */
    public function periodEnd(Carbon $date): Carbon
    {
        // startOfDay() after endOf*() so this stays midnight-aligned like a
        // date-cast Eloquent attribute — endOf*() alone sets 23:59:59.999999,
        // which makes diffInDays() against a midnight periodStart() return a
        // near-integer float instead of a clean day count.
        return match ($this) {
            self::Monthly => $date->copy()->endOfMonth()->startOfDay(),
            self::Quarterly => $date->copy()->endOfQuarter()->startOfDay(),
            self::Yearly => $date->copy()->endOfYear()->startOfDay(),
        };
    }

    /**
     * Start of the calendar month/quarter/year containing $date. Paired
     * with periodEnd() to derive a *nominal* full-length cycle even when
     * the actual billed period is shorter (a mid-cycle start) — proration
     * always divides by the full cycle length, never by the short period
     * itself, or a mid-cycle start would wrongly compute a 100% ratio.
     */
    public function periodStart(Carbon $date): Carbon
    {
        return match ($this) {
            self::Monthly => $date->copy()->startOfMonth(),
            self::Quarterly => $date->copy()->startOfQuarter(),
            self::Yearly => $date->copy()->startOfYear(),
        };
    }
}
