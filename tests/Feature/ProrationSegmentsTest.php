<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPlanChange;
use App\Services\ProrationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProrationSegmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_plan_changes_produces_a_single_segment_for_the_whole_period(): void
    {
        $merchant = Merchant::factory()->create();
        $subscription = Subscription::factory()->for($merchant)->create();

        $segments = (new ProrationService)->calculateSegments(
            $subscription, Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31')
        );

        $this->assertCount(1, $segments);
        $this->assertSame($subscription->plan_id, $segments[0]['plan_id']);
        $this->assertTrue($segments[0]['start']->isSameDay(Carbon::parse('2026-01-01')));
        $this->assertTrue($segments[0]['end']->isSameDay(Carbon::parse('2026-01-31')));
    }

    public function test_a_mid_cycle_plan_change_splits_the_period_into_two_contiguous_segments(): void
    {
        $merchant = Merchant::factory()->create();
        $oldPlan = Plan::factory()->for($merchant)->create();
        $newPlan = Plan::factory()->for($merchant)->create();
        $subscription = Subscription::factory()->for($merchant)->create(['plan_id' => $newPlan->id]);

        SubscriptionPlanChange::create([
            'subscription_id' => $subscription->id,
            'old_plan_id' => $oldPlan->id,
            'new_plan_id' => $newPlan->id,
            'changed_at' => now(),
            'effective_date' => '2026-01-16',
        ]);

        $segments = (new ProrationService)->calculateSegments(
            $subscription, Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31')
        );

        $this->assertCount(2, $segments);

        $this->assertSame($oldPlan->id, $segments[0]['plan_id']);
        $this->assertTrue($segments[0]['start']->isSameDay(Carbon::parse('2026-01-01')));
        $this->assertTrue($segments[0]['end']->isSameDay(Carbon::parse('2026-01-15')));

        $this->assertSame($newPlan->id, $segments[1]['plan_id']);
        $this->assertTrue($segments[1]['start']->isSameDay(Carbon::parse('2026-01-16')));
        $this->assertTrue($segments[1]['end']->isSameDay(Carbon::parse('2026-01-31')));
    }

    public function test_a_change_effective_on_the_periods_first_day_drops_the_zero_day_leading_segment(): void
    {
        $merchant = Merchant::factory()->create();
        $oldPlan = Plan::factory()->for($merchant)->create();
        $newPlan = Plan::factory()->for($merchant)->create();
        $subscription = Subscription::factory()->for($merchant)->create(['plan_id' => $newPlan->id]);

        SubscriptionPlanChange::create([
            'subscription_id' => $subscription->id,
            'old_plan_id' => $oldPlan->id,
            'new_plan_id' => $newPlan->id,
            'changed_at' => now(),
            'effective_date' => '2026-01-01',
        ]);

        $segments = (new ProrationService)->calculateSegments(
            $subscription, Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31')
        );

        $this->assertCount(1, $segments);
        $this->assertSame($newPlan->id, $segments[0]['plan_id']);
    }
}
