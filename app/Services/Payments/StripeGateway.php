<?php

namespace App\Services\Payments;

use App\Models\Obligation;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\RentOrder;
use App\Models\SaasOrder;
use RuntimeException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeGateway
{
    private function client(): StripeClient
    {
        $secret = (string) config('lokalpay.stripe.secret');
        if ($secret === '') throw new RuntimeException('Stripe nie jest skonfigurowany.');
        return new StripeClient($secret);
    }

    public function createRentCheckout(RentOrder $order, Obligation $obligation): array
    {
        $session = $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'success_url' => route('tenant.portal', ['payment' => 'success']),
            'cancel_url' => route('tenant.portal', ['payment' => 'cancelled']),
            'customer_email' => $obligation->lease->tenant_email,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($order->currency),
                    'unit_amount' => $order->amount_cents,
                    'product_data' => ['name' => "Czynsz {$obligation->period} — {$obligation->property->name}"],
                ],
            ]],
            'metadata' => ['kind' => 'rent', 'local_order_id' => (string) $order->id],
            'payment_intent_data' => ['metadata' => ['kind' => 'rent', 'local_order_id' => (string) $order->id]],
        ], ['idempotency_key' => $order->idempotency_key]);
        return ['id' => $session->id, 'url' => $session->url, 'expires_at' => $session->expires_at];
    }

    public function createSubscriptionCheckout(SaasOrder $order, Organization $organization, Plan $plan): array
    {
        $price = (string) config("lokalpay.stripe.prices.{$plan->code}");
        if ($price === '') throw new RuntimeException('Brak identyfikatora ceny Stripe dla planu.');
        $session = $this->client()->checkout->sessions->create([
            'mode' => 'subscription',
            'success_url' => route('billing.return', ['status' => 'success']),
            'cancel_url' => route('billing.return', ['status' => 'cancelled']),
            'customer_email' => $organization->billing_email ?: $organization->owner->email,
            'line_items' => [['price' => $price, 'quantity' => 1]],
            'metadata' => ['kind' => 'saas', 'local_order_id' => (string) $order->id],
            'subscription_data' => ['metadata' => ['local_order_id' => (string) $order->id]],
        ], ['idempotency_key' => $order->idempotency_key]);
        return ['id' => $session->id, 'url' => $session->url, 'expires_at' => $session->expires_at];
    }

    public function billingPortal(string $customerId): string
    {
        return $this->client()->billingPortal->sessions->create([
            'customer' => $customerId,
            'return_url' => route('dashboard'),
        ])->url;
    }

    public function subscription(string $subscriptionId): object
    {
        return $this->client()->subscriptions->retrieve($subscriptionId, [
            'expand' => ['items.data.price'],
        ]);
    }

    public function verifyWebhook(string $payload, string $signature): object
    {
        $secret = (string) config('lokalpay.stripe.webhook_secret');
        if ($secret === '') throw new RuntimeException('Brak sekretu webhook Stripe.');
        return Webhook::constructEvent($payload, $signature, $secret, 300);
    }
}
