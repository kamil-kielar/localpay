<?php

namespace App\Http\Controllers;

use App\Models\RentOrder;
use App\Models\SaasOrder;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Payments\PayUGateway;
use App\Services\Payments\StripeGateway;
use App\Services\Payments\WebhookProcessor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class WebhookController extends Controller
{
    public function stripe(Request $request, StripeGateway $stripe, WebhookProcessor $processor): Response
    {
        $payload = $request->getContent();
        try {
            $event = $stripe->verifyWebhook($payload, (string) $request->header('Stripe-Signature'));
        } catch (Throwable) {
            return response('Invalid signature', 400);
        }
        $record = $processor->reserve('stripe', (string) $event->id, (string) $event->type, $payload);
        if (!$record) return response('Already processed', 200);
        try {
            if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
                $session = $event->data->object;
                if (($session->payment_status ?? null) !== 'paid') {
                    $record->update(['status' => 'ignored', 'processed_at' => now()]);
                    return response('Ignored', 200);
                }
                $localId = (int) ($session->metadata->local_order_id ?? 0);
                $kind = (string) ($session->metadata->kind ?? '');
                if ($kind === 'rent') {
                    $order = RentOrder::query()->whereKey($localId)->where('provider_order_id', $session->id)->firstOrFail();
                    $processor->completeRent($order, (int) $session->amount_total, strtoupper((string) $session->currency), (string) $session->payment_intent);
                } elseif ($kind === 'saas') {
                    $order = SaasOrder::query()->with('plan')->whereKey($localId)->where('provider_order_id', $session->id)->firstOrFail();
                    $subscription = $stripe->subscription((string) $session->subscription);
                    $this->assertStripeSubscriptionMatches($subscription, $order);
                    $processor->completeSaas($order, (int) $session->amount_total, strtoupper((string) $session->currency), [
                        'subscription_id' => (string) $subscription->id,
                        'customer_id' => (string) $session->customer,
                        'status' => (string) $subscription->status,
                        'period_end' => isset($subscription->current_period_end)
                            ? date('Y-m-d H:i:s', $subscription->current_period_end)
                            : null,
                        'event_created_at' => 0,
                        'cancel_at_period_end' => (bool) ($subscription->cancel_at_period_end ?? false),
                    ]);
                }
            }
            if ($event->type === 'checkout.session.expired') {
                RentOrder::where('provider_order_id', $event->data->object->id)->whereNot('status', 'paid')->update(['status' => 'expired']);
                SaasOrder::where('provider_order_id', $event->data->object->id)->whereNot('status', 'paid')->update(['status' => 'expired']);
            }
            if (str_starts_with($event->type, 'customer.subscription.')) {
                $subscription = $stripe->subscription((string) $event->data->object->id);
                $localOrderId = (int) ($subscription->metadata->local_order_id ?? 0);
                $order = SaasOrder::query()->with(['plan', 'organization'])
                    ->whereKey($localOrderId)->where('provider', 'stripe')->first();
                if (! $order) {
                    throw new \RuntimeException('Subskrypcja Stripe nie jest powiązana z lokalnym zamówieniem.');
                }
                if ($order->provider_subscription_id
                    && ! hash_equals($order->provider_subscription_id, (string) $subscription->id)) {
                    throw new \RuntimeException('Identyfikator subskrypcji nie zgadza się z lokalnym zamówieniem.');
                }
                $resolvedPlan = $this->resolveStripePlan($subscription);
                if (! $resolvedPlan) {
                    $existingSubscription = Subscription::where(
                        'provider_subscription_id',
                        (string) $subscription->id
                    )->first();
                    if ($existingSubscription) {
                        $existingSubscription->update(['status' => 'unsupported_price']);
                        if ($existingSubscription->organization->current_subscription_id === $existingSubscription->id) {
                            $freeId = Plan::where('code', 'free')->value('id');
                            $existingSubscription->organization()->update([
                                'plan_id' => $freeId,
                                'current_subscription_id' => null,
                                'plan_expires_at' => null,
                            ]);
                        }
                    }
                    throw new \RuntimeException('Cena subskrypcji Stripe nie jest obsługiwana.');
                }
                $order->update(['provider_subscription_id' => (string) $subscription->id]);
                $localSubscription = Subscription::firstOrNew([
                    'provider_subscription_id' => (string) $subscription->id,
                ]);
                $localSubscription->fill([
                    'organization_id' => $order->organization_id,
                    'plan_id' => $resolvedPlan->id,
                    'provider' => 'stripe',
                    'provider_customer_id' => (string) ($subscription->customer ?? ''),
                    'status' => (string) $subscription->status,
                    'current_period_end' => isset($subscription->current_period_end) ? date('Y-m-d H:i:s', $subscription->current_period_end) : null,
                    'last_event_created_at' => (int) $event->created,
                    'cancel_at_period_end' => (bool) ($subscription->cancel_at_period_end ?? false),
                ]);
                $localSubscription->save();
                if ($order->status === 'paid'
                    && in_array((string) $subscription->status, ['active', 'trialing'], true)) {
                    $order->organization()->update([
                        'plan_id' => $resolvedPlan->id,
                        'current_subscription_id' => $localSubscription->id,
                        'plan_expires_at' => null,
                    ]);
                } elseif ($order->status === 'paid'
                    && $order->organization->current_subscription_id === $localSubscription->id
                    && in_array((string) $subscription->status, ['canceled', 'incomplete_expired'], true)) {
                    $freeId = Plan::where('code', 'free')->value('id');
                    $localSubscription->organization()->update([
                        'plan_id' => $freeId,
                        'current_subscription_id' => null,
                        'plan_expires_at' => null,
                    ]);
                }
            }

            $record->update(['status' => 'processed', 'processed_at' => now()]);
            return response('OK', 200);
        } catch (Throwable $exception) {
            report($exception);
            $record->update(['status' => 'failed', 'failure_reason' => mb_substr($exception->getMessage(), 0, 2000)]);
            return response('Processing failed', 422);
        }
    }

    public function payu(Request $request, PayUGateway $payu, WebhookProcessor $processor): Response
    {
        $payload = $request->getContent();
        if (!$payu->verifySignature($payload, (string) $request->header('OpenPayU-Signature'))) return response('Invalid signature', 400);
        $data = json_decode($payload, true);
        $orderData = $data['order'] ?? [];
        $providerId = (string) ($orderData['orderId'] ?? '');
        $status = (string) ($orderData['status'] ?? '');
        $eventId = $providerId.':'.$status;
        $record = $processor->reserve('payu', $eventId, 'order.'.$status, $payload);
        if (!$record) return response('Already processed', 200);
        try {
            if ((string) ($orderData['merchantPosId'] ?? '') !== (string) config('lokalpay.payu.pos_id')) throw new \RuntimeException('Nieprawidłowy POS PayU.');
            if ($status === 'COMPLETED') {
                $external = (string) ($orderData['extOrderId'] ?? '');
                $amount = (int) ($orderData['totalAmount'] ?? 0);
                $currency = (string) ($orderData['currencyCode'] ?? '');
                $rent = RentOrder::query()->where('idempotency_key', $external)->where('provider_order_id', $providerId)->first();
                $saas = SaasOrder::query()->where('idempotency_key', $external)->where('provider_order_id', $providerId)->first();
                if ($rent) $processor->completeRent($rent, $amount, $currency, $providerId);
                elseif ($saas) $processor->completeSaas($saas, $amount, $currency);
                else throw new \RuntimeException('Brak lokalnego zamówienia PayU.');
            }
            $record->update(['status' => 'processed', 'processed_at' => now()]);
            return response('OK', 200);
        } catch (Throwable $exception) {
            report($exception);
            $record->update(['status' => 'failed', 'failure_reason' => mb_substr($exception->getMessage(), 0, 2000)]);
            return response('Processing failed', 422);
        }
    }

    private function assertStripeSubscriptionMatches(object $subscription, SaasOrder $order): void
    {
        $resolvedPlan = $this->resolveStripePlan($subscription);
        if (! $resolvedPlan || $resolvedPlan->id !== $order->plan_id) {
            throw new \RuntimeException('Cena subskrypcji Stripe nie zgadza się z lokalnym zamówieniem.');
        }
    }

    private function resolveStripePlan(object $subscription): ?Plan
    {
        $price = $subscription->items->data[0]->price ?? null;
        $priceId = (string) ($price->id ?? '');
        $currency = strtoupper((string) ($price->currency ?? ''));
        $amount = (int) ($price->unit_amount ?? -1);
        if ($currency !== 'PLN') return null;

        foreach (['growth', 'pro'] as $code) {
            $configuredPrice = (string) config("lokalpay.stripe.prices.{$code}");
            $plan = Plan::where('code', $code)->where('active', true)->first();
            if ($plan && $configuredPrice !== '' && hash_equals($configuredPrice, $priceId)
                && $amount === $plan->price_cents) {
                return $plan;
            }
        }

        return null;
    }
}
