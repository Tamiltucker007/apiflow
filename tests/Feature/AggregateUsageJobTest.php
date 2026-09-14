<?php

namespace Tests\Feature;

use App\Jobs\AggregateUsageJob;
use App\Models\Customer;
use App\Models\DailyUsage;
use App\Models\Merchant;
use App\Models\Subscription;
use App\Models\UsageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AggregateUsageJobTest extends TestCase
{
    use RefreshDatabase;

    private function makeSubscription(Merchant $merchant): Subscription
    {
        $customer = Customer::factory()->for($merchant)->create();

        return Subscription::factory()->for($merchant)->for($customer)->create();
    }

    public function test_it_sums_same_day_events_into_one_daily_usage_row(): void
    {
        $merchant = Merchant::factory()->create();
        $subscription = $this->makeSubscription($merchant);

        UsageEvent::factory()->count(100)->create([
            'merchant_id' => $merchant->id,
            'customer_id' => $subscription->customer_id,
            'subscription_id' => $subscription->id,
            'recorded_date' => '2026-01-15',
            'units' => 1,
        ]);

        AggregateUsageJob::dispatch();

        $this->assertSame(1, DailyUsage::count());
        $this->assertSame(100, DailyUsage::first()->total_units);
        $this->assertSame(0, UsageEvent::unaggregated()->count());
    }

    public function test_running_it_twice_does_not_double_count(): void
    {
        $merchant = Merchant::factory()->create();
        $subscription = $this->makeSubscription($merchant);

        UsageEvent::factory()->count(10)->create([
            'merchant_id' => $merchant->id,
            'customer_id' => $subscription->customer_id,
            'subscription_id' => $subscription->id,
            'recorded_date' => '2026-01-15',
            'units' => 5,
        ]);

        AggregateUsageJob::dispatch();
        AggregateUsageJob::dispatch();

        $this->assertSame(50, DailyUsage::first()->total_units);
    }

    public function test_it_aggregates_multiple_merchants_in_one_run(): void
    {
        $merchantA = Merchant::factory()->create();
        $merchantB = Merchant::factory()->create();
        $subA = $this->makeSubscription($merchantA);
        $subB = $this->makeSubscription($merchantB);

        UsageEvent::factory()->create([
            'merchant_id' => $merchantA->id, 'customer_id' => $subA->customer_id,
            'subscription_id' => $subA->id, 'recorded_date' => '2026-01-15', 'units' => 7,
        ]);
        UsageEvent::factory()->create([
            'merchant_id' => $merchantB->id, 'customer_id' => $subB->customer_id,
            'subscription_id' => $subB->id, 'recorded_date' => '2026-01-15', 'units' => 3,
        ]);

        AggregateUsageJob::dispatch();

        $this->assertSame(2, DailyUsage::count());
        $this->assertSame(7, DailyUsage::where('merchant_id', $merchantA->id)->first()->total_units);
        $this->assertSame(3, DailyUsage::where('merchant_id', $merchantB->id)->first()->total_units);
    }

    public function test_a_run_started_while_the_lock_is_held_does_not_process_events(): void
    {
        $merchant = Merchant::factory()->create();
        $subscription = $this->makeSubscription($merchant);

        UsageEvent::factory()->create([
            'merchant_id' => $merchant->id,
            'customer_id' => $subscription->customer_id,
            'subscription_id' => $subscription->id,
            'recorded_date' => '2026-01-15',
            'units' => 9,
        ]);

        $lockKey = (new WithoutOverlapping('aggregate-usage'))->getLockKey(new AggregateUsageJob);
        $held = Cache::lock($lockKey, 900);
        $held->get();

        try {
            AggregateUsageJob::dispatch();

            $this->assertSame(0, DailyUsage::count());
            $this->assertSame(1, UsageEvent::unaggregated()->count());
        } finally {
            $held->release();
        }
    }
}
