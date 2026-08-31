<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Plan;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\StripeGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function plans(): JsonResponse { return response()->json(Plan::query()->where('active', true)->orderBy('price_cents')->get()); }

    public function checkout(Request $request, Organization $organization, CheckoutService $checkout): JsonResponse
    {
        $data = $request->validate(['plan' => ['required', 'exists:plans,code'], 'provider' => ['required', 'in:stripe,payu']]);
        $plan = Plan::query()->where('code', $data['plan'])->firstOrFail();
        $order = $checkout->plan($organization, $plan, $data['provider'], $request->ip());
        return response()->json(['checkout_url' => $order->checkout_url, 'payu_notice' => $data['provider'] === 'payu' ? 'Dostęp na 30 dni, bez automatycznego odnowienia.' : null]);
    }

    public function portal(Organization $organization, StripeGateway $stripe): RedirectResponse
    {
        $customer = $organization->subscriptions()->where('provider', 'stripe')->whereNotNull('provider_customer_id')->latest()->value('provider_customer_id');
        abort_unless($customer, 422, 'Brak aktywnego profilu rozliczeniowego Stripe.');
        return redirect()->away($stripe->billingPortal($customer));
    }
}
