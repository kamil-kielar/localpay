<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaseResource;
use App\Http\Resources\ObligationResource;
use App\Http\Resources\PropertyResource;
use App\Models\Organization;
use App\Services\PlanEntitlementService;
use App\Services\PortfolioAnalyticsService;
use Illuminate\Http\JsonResponse;

class DashboardApiController extends Controller
{
    public function __invoke(Organization $organization, PortfolioAnalyticsService $analytics, PlanEntitlementService $plans): JsonResponse
    {
        $organization->load('plan');
        $properties = $organization->properties()->withCount('leases')->latest()->get();
        $leases = \App\Models\Lease::query()->where('organization_id', $organization->id)->with('property')->latest()->limit(100)->get();
        $obligations = \App\Models\Obligation::query()->where('organization_id', $organization->id)->with(['property', 'lease'])->orderBy('due_date')->limit(200)->get();
        $revenues = \App\Models\RevenuePayment::query()->where('organization_id', $organization->id)->with('property')->latest('paid_on')->limit(200)->get();
        $summary = $analytics->summarize($organization);
        if (!$plans->allows($organization, 'forecasts')) unset($summary['forecast']);
        if (!$plans->allows($organization, 'year_comparison')) unset($summary['year_totals']);
        return response()->json([
            'organization' => ['id' => $organization->public_id, 'name' => $organization->name, 'status' => $organization->status],
            'plan' => $plans->effectivePlan($organization),
            'entitlements' => $plans->effectivePlan($organization)->features,
            'analytics' => $summary,
            'properties' => PropertyResource::collection($properties),
            'leases' => LeaseResource::collection($leases),
            'obligations' => ObligationResource::collection($obligations),
            'revenues' => $revenues->map(fn ($item) => [
                'id' => $item->public_id, 'property' => $item->property->name, 'amount_cents' => $item->amount_cents,
                'source' => $item->source, 'paid_on' => $item->paid_on->toDateString(),
            ]),
            'notifications' => auth()->user()->notifications()->latest()->limit(30)->get(),
            'billing' => [
                'has_stripe_customer' => $organization->subscriptions()->where('provider', 'stripe')->whereNotNull('provider_customer_id')->exists(),
                'plan_expires_at' => $organization->plan_expires_at,
            ],
            'invitations' => \App\Models\TenantInvitation::query()->where('organization_id', $organization->id)->with('lease.property')->latest()->limit(100)->get()->map(fn ($item) => [
                'id' => $item->public_id, 'lease_id' => $item->lease->public_id, 'property' => $item->lease->property->name,
                'email' => $item->email, 'status' => $item->status, 'expires_at' => $item->expires_at,
            ]),
        ]);
    }
}
