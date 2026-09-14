<?php

namespace Tests\Unit;

use App\Enums\BillingCycle;
use Carbon\Carbon;
use Tests\TestCase;

class BillingCycleTest extends TestCase
{
    public function test_monthly_period_spans_the_full_calendar_month(): void
    {
        $start = BillingCycle::Monthly->periodStart(Carbon::parse('2026-01-15'));
        $end = BillingCycle::Monthly->periodEnd(Carbon::parse('2026-01-15'));

        $this->assertTrue($start->isSameDay(Carbon::parse('2026-01-01')));
        $this->assertTrue($end->isSameDay(Carbon::parse('2026-01-31')));
    }

    public function test_period_end_is_midnight_aligned_so_day_diffs_are_whole_numbers(): void
    {
        $start = BillingCycle::Monthly->periodStart(Carbon::parse('2026-01-15'));
        $end = BillingCycle::Monthly->periodEnd(Carbon::parse('2026-01-15'));

        // Regression: endOfMonth() alone leaves a 23:59:59.999999 time
        // component, which turns this into 30.999999999988425 instead of 30.
        $this->assertSame(30.0, $start->diffInDays($end));
    }

    public function test_quarterly_and_yearly_periods_align_to_their_own_calendar_boundaries(): void
    {
        $this->assertTrue(BillingCycle::Quarterly->periodStart(Carbon::parse('2026-05-10'))->isSameDay(Carbon::parse('2026-04-01')));
        $this->assertTrue(BillingCycle::Quarterly->periodEnd(Carbon::parse('2026-05-10'))->isSameDay(Carbon::parse('2026-06-30')));

        $this->assertTrue(BillingCycle::Yearly->periodStart(Carbon::parse('2026-05-10'))->isSameDay(Carbon::parse('2026-01-01')));
        $this->assertTrue(BillingCycle::Yearly->periodEnd(Carbon::parse('2026-05-10'))->isSameDay(Carbon::parse('2026-12-31')));
    }

    public function test_months_per_cycle(): void
    {
        $this->assertSame(1, BillingCycle::Monthly->months());
        $this->assertSame(3, BillingCycle::Quarterly->months());
        $this->assertSame(12, BillingCycle::Yearly->months());
    }
}
