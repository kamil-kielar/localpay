<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Services\PlanEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanEntitlementTest extends TestCase
{
    use RefreshDatabase;
    public function test_growth_has_forecasts_and_free_does_not(): void
    {
        $service = app(PlanEntitlementService::class);
        $org = Organization::factory()->create(['plan_id' => Plan::where('code', 'free')->value('id')]);
        $this->assertFalse($service->allows($org->fresh('plan'), 'forecasts'));
        $org->update(['plan_id' => Plan::where('code', 'growth')->value('id')]);
        $this->assertTrue($service->allows($org->fresh('plan'), 'forecasts'));
    }
}
