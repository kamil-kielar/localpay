<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Plan;
use Illuminate\Validation\ValidationException;

class PlanEntitlementService
{
    public function effectivePlan(Organization $organization): Plan
    {
        $organization->loadMissing(['plan', 'currentSubscription']);
        if ($organization->currentSubscription
            && ! in_array($organization->currentSubscription->status, ['active', 'trialing'], true)) {
            return Plan::query()->where('code', 'free')->firstOrFail();
        }
        if ($organization->plan && (!$organization->plan_expires_at || $organization->plan_expires_at->isFuture())) {
            return $organization->plan;
        }
        return Plan::query()->where('code', 'free')->firstOrFail();
    }

    public function assertCanAddProperty(Organization $organization): void
    {
        $limit = $this->effectivePlan($organization)->property_limit;
        if ($organization->properties()->count() >= $limit) {
            throw ValidationException::withMessages(['plan' => "Limit planu wynosi {$limit} nieruchomości."]);
        }
    }

    public function allows(Organization $organization, string $feature): bool
    {
        return in_array($feature, $this->effectivePlan($organization)->features ?? [], true);
    }
}
