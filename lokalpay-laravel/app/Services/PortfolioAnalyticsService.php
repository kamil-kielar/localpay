<?php

namespace App\Services;

use App\Models\Organization;

class PortfolioAnalyticsService
{
    public function summarize(Organization $organization): array
    {
        $properties = $organization->properties()->with('revenues')->get();
        $cost = (int) $properties->sum('purchase_cost_cents');
        $revenue = (int) $properties->sum(fn ($property) => $property->revenues->sum('amount_cents'));
        $years = [];
        foreach ($properties->flatMap->revenues as $payment) {
            $year = $payment->paid_on->year;
            $years[$year] = ($years[$year] ?? 0) + $payment->amount_cents;
        }
        ksort($years);
        return [
            'portfolio_value_cents' => $cost,
            'revenue_cents' => $revenue,
            'roi_percent' => $this->roi($revenue, $cost),
            'recovery_percent' => $cost > 0 ? round(min(100, $revenue / $cost * 100), 2) : 0.0,
            'remaining_cents' => max(0, $cost - $revenue),
            'year_totals' => $years,
            'forecast' => $this->forecast($cost, $revenue, array_values($years)),
        ];
    }

    public function roi(int $revenueCents, int $costCents): float
    {
        return $costCents > 0 ? round($revenueCents / $costCents * 100, 2) : 0.0;
    }

    public function forecast(int $costCents, int $revenueCents, array $annualRevenueCents): array
    {
        $positive = array_values(array_filter($annualRevenueCents, fn ($value) => $value > 0));
        $average = $positive ? (int) round(array_sum($positive) / count($positive)) : 0;
        $remaining = max(0, $costCents - $revenueCents);
        $months = $average > 0 ? (int) ceil($remaining / $average * 12) : null;
        return ['average_annual_cents' => $average, 'months_to_recovery' => $months];
    }
}
