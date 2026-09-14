<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\Plan;
use App\Services\PlanPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PlanPricingCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_plan_lookup_is_cached_after_the_first_call(): void
    {
        $plan = Plan::factory()->for(Merchant::factory()->create())->create();

        $this->assertFalse(Cache::has("plan:{$plan->id}"));

        $result = app(PlanPricingService::class)->getCachedPlan($plan->id);

        $this->assertSame($plan->id, $result->id);
        $this->assertTrue(Cache::has("plan:{$plan->id}"));
    }

    public function test_updating_a_plan_invalidates_its_cache_immediately(): void
    {
        $plan = Plan::factory()->for(Merchant::factory()->create())->create(['base_price_cents' => 100000]);
        $pricing = app(PlanPricingService::class);

        $pricing->getCachedPlan($plan->id);
        $this->assertTrue(Cache::has("plan:{$plan->id}"));

        $plan->update(['base_price_cents' => 250000]);

        $this->assertFalse(Cache::has("plan:{$plan->id}"));
        $this->assertSame(250000, $pricing->getCachedPlan($plan->id)->base_price_cents);
    }

    public function test_deleting_a_plan_invalidates_its_cache(): void
    {
        $plan = Plan::factory()->for(Merchant::factory()->create())->create();
        app(PlanPricingService::class)->getCachedPlan($plan->id);

        $plan->delete();

        $this->assertFalse(Cache::has("plan:{$plan->id}"));
    }
}
