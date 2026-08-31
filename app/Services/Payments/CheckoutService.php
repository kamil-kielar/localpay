<?php

namespace App\Services\Payments;

use App\Models\Obligation;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\RentOrder;
use App\Models\SaasOrder;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(private StripeGateway $stripe, private PayUGateway $payu) {}

    public function rent(Obligation $obligation, string $provider, ?int $tenantId, string $ip): RentOrder
    {
        $order = DB::transaction(function () use ($obligation, $provider, $tenantId): RentOrder {
            $locked = Obligation::query()->with(['lease', 'property'])->lockForUpdate()->findOrFail($obligation->id);
            if ($locked->status === 'paid' || $locked->status === 'void') {
                throw ValidationException::withMessages(['obligation' => 'Należność nie może zostać opłacona.']);
            }
            $existing = RentOrder::query()->where('obligation_id', $locked->id)
                ->whereIn('status', ['creating', 'pending'])->latest()->lockForUpdate()->first();
            if ($existing && $existing->status === 'creating') {
                if ($existing->updated_at->isAfter(now()->subMinutes(10))) {
                    throw ValidationException::withMessages(['obligation' => 'Płatność jest już przygotowywana.']);
                }
                $existing->update(['status' => 'failed']);
                $existing = null;
            }
            if ($existing && $existing->checkout_expires_at?->isPast()) {
                $existing->update(['status' => 'expired']);
                $existing = null;
            }
            if ($existing && $existing->provider !== $provider) {
                throw ValidationException::withMessages(['provider' => 'Dokończ płatność u wcześniej wybranego operatora.']);
            }
            if ($existing && (!$existing->checkout_expires_at || $existing->checkout_expires_at->isFuture())) return $existing;
            return RentOrder::query()->create([
                'organization_id' => $locked->organization_id,
                'obligation_id' => $locked->id,
                'tenant_user_id' => $tenantId,
                'provider' => $provider,
                'status' => 'creating',
                'amount_cents' => $locked->amount_cents - $locked->paid_amount_cents,
                'currency' => $locked->currency,
                'idempotency_key' => 'rent_'.Str::uuid(),
            ]);
        });
        if ($order->checkout_url) return $order;
        $obligation->loadMissing(['lease', 'property']);
        try {
            $remote = $provider === 'stripe'
                ? $this->stripe->createRentCheckout($order, $obligation)
                : $this->payu->createRentOrder($order, $obligation, $ip);
            $order->update(['provider_order_id' => $remote['id'], 'checkout_url' => $remote['url'], 'checkout_expires_at' => date('Y-m-d H:i:s', $remote['expires_at']), 'status' => 'pending']);
        } catch (\Throwable $exception) {
            $order->update(['status' => 'failed']);
            throw $exception;
        }
        return $order->fresh();
    }

    public function plan(Organization $organization, Plan $plan, string $provider, string $ip): SaasOrder
    {
        if ($plan->code === 'free') throw ValidationException::withMessages(['plan' => 'Plan Free nie wymaga płatności.']);
        $order = DB::transaction(function () use ($organization, $plan, $provider): SaasOrder {
            $locked = Organization::query()->lockForUpdate()->findOrFail($organization->id);
            if ($provider === 'stripe') {
                $activeSubscription = Subscription::query()
                    ->where('organization_id', $locked->id)
                    ->whereNotIn('status', ['canceled', 'incomplete_expired'])
                    ->lockForUpdate()
                    ->first();
                if ($activeSubscription) {
                    throw ValidationException::withMessages([
                        'plan' => 'Aktywną subskrypcją Stripe zarządzaj przez portal rozliczeniowy.',
                    ]);
                }
            }
            $existing = SaasOrder::query()
                ->where('organization_id', $locked->id)
                ->whereIn('status', ['creating', 'pending'])
                ->latest()
                ->lockForUpdate()
                ->first();
            if ($existing && $existing->status === 'creating') {
                if ($existing->updated_at->isAfter(now()->subMinutes(10))) {
                    throw ValidationException::withMessages(['plan' => 'Płatność za plan jest już przygotowywana.']);
                }
                $existing->update(['status' => 'failed']);
                $existing = null;
            }
            if ($existing && $existing->expires_at?->isPast()) {
                $existing->update(['status' => 'expired']);
                $existing = null;
            }
            if ($existing && $existing->provider !== $provider) {
                throw ValidationException::withMessages(['provider' => 'Dokończ rozpoczęty zakup u wcześniej wybranego operatora.']);
            }
            if ($existing && (! $existing->expires_at || $existing->expires_at->isFuture())) {
                if ($existing->plan_id !== $plan->id) {
                    throw ValidationException::withMessages(['plan' => 'Dokończ lub anuluj rozpoczęty zakup planu.']);
                }
                return $existing;
            }
            return SaasOrder::query()->create([
                'organization_id' => $locked->id,
                'plan_id' => $plan->id,
                'provider' => $provider,
                'kind' => $provider === 'stripe' ? 'subscription' : 'one_time_30_days',
                'status' => 'creating',
                'amount_cents' => $plan->price_cents,
                'currency' => 'PLN',
                'idempotency_key' => 'saas_'.Str::uuid(),
            ]);
        });
        if ($order->checkout_url) return $order;
        $organization->loadMissing('owner');
        try {
            $remote = $provider === 'stripe'
                ? $this->stripe->createSubscriptionCheckout($order, $organization, $plan)
                : $this->payu->createPlanOrder($order, $organization, $plan, $ip);
            $order->update(['provider_order_id' => $remote['id'], 'checkout_url' => $remote['url'], 'expires_at' => date('Y-m-d H:i:s', $remote['expires_at']), 'status' => 'pending']);
        } catch (\Throwable $exception) {
            $order->update(['status' => 'failed']);
            throw $exception;
        }
        return $order->fresh();
    }
}
