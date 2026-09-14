<?php

namespace Tests\Unit;

use App\Services\ProrationService;
use Tests\TestCase;

class ProrationServiceTest extends TestCase
{
    private ProrationService $proration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proration = new ProrationService;
    }

    public function test_a_full_cycle_segment_returns_the_full_amount(): void
    {
        $this->assertSame(100000, $this->proration->prorate(100000, 31, 31));
    }

    public function test_a_partial_segment_is_prorated_and_rounded_to_the_nearest_cent(): void
    {
        // 100000 * 17 / 31 = 54838.709... -> rounds to 54839.
        $this->assertSame(54839, $this->proration->prorate(100000, 17, 31));
    }

    public function test_zero_total_cycle_days_falls_back_to_the_full_amount_instead_of_dividing_by_zero(): void
    {
        $this->assertSame(100000, $this->proration->prorate(100000, 5, 0));
    }
}
