<?php

namespace Tests\Unit;

use App\Services\PortfolioAnalyticsService;
use PHPUnit\Framework\TestCase;

class PortfolioAnalyticsServiceTest extends TestCase
{
    public function test_roi_is_calculated_and_zero_cost_is_safe(): void
    {
        $service = new PortfolioAnalyticsService();
        $this->assertSame(10.0, $service->roi(10000, 100000));
        $this->assertSame(0.0, $service->roi(10000, 0));
    }
    public function test_forecast_uses_average_positive_years(): void
    {
        $forecast = (new PortfolioAnalyticsService())->forecast(1200000, 200000, [0, 100000, 300000]);
        $this->assertSame(200000, $forecast['average_annual_cents']);
        $this->assertSame(60, $forecast['months_to_recovery']);
    }
}
