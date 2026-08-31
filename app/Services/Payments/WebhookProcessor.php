<?php

namespace App\Services\Payments;

use App\Models\RentOrder;
use App\Models\SaasOrder;
use App\Models\Subscription;
use App\Models\WebhookEvent;
use App\Notifications\PaymentReceiptNotification;
use App\Services\ObligationPaymentService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WebhookProcessor
{
    public function __construct(private ObligationPaymentService $payments) {}

    public function reserve(string $provider, string $eventId, ?string $type, string $payload): ?WebhookEvent
    {
        $existing = WebhookEvent::query()->where('provider', $provider)->where('provider_event_id', $eventId)->first();
        if ($existing) {
            if ($existing->status === 'failed' || ($existing->status === 'received' && $existing->created_at->lt(now()->subMinutes(5)))) {
                $existing->update(['status' => 'received', 'failure_reason' => null]);
                return $existing;
            }
            return null;
        }
        try {
            return WebhookEvent::query()->create([
                'provider' => $provider, 'provider_event_id' => $eventId, 'event_type' => $type,
                'status' => 'received', 'payload_hash' => hash('sha256', $payload),
            ]);
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')) return null;
            throw $exception;
        }
    }

    public function completeRent(RentOrder $order, int $amountCents, string $currency, string $transactionId): void
    {
        DB::transaction(function () use ($order, $amountCents, $currency, $transactionId): void {
            $locked = RentOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->status === 'paid') return;
            if ($amountCents !== $locked->amount_cents || strtoupper($currency) !== $locked->currency) {
                throw new RuntimeException('Kwota lub waluta płatności czynszu nie zgadza się z zamówieniem.');
            }
            $this->payments->record($locked->obligation, $amountCents, $locked->provider, $transactionId, $locked->tenant_user_id);
            $locked->update([
                'status' => 'paid',
                'paid_at' => now(),
                'provider_subscription_id' => $providerData['subscription_id'] ?? null,
            ]);
            if ($locked->obligation->lease->tenant) {
                $locked->obligation->lease->tenant->notify(new PaymentReceiptNotification($locked->obligation));
            }
            $locked->organization->owner->notify(new PaymentReceiptNotification($locked->obligation));
        });
    }

    public function completeSaas(SaasOrder $order, int $amountCents, string $currency, array $providerData = []): void
    {
        DB::transaction(function () use ($order, $amountCents, $currency, $providerData): void {
            $locked = SaasOrder::query()->with(['organization', 'plan'])->lockForUpdate()->findOrFail($order->id);
            if ($locked->status === 'paid') return;
            if ($amountCents !== $locked->amount_cents || strtoupper($currency) !== $locked->currency) {
                throw new RuntimeException('Kwota lub waluta planu nie zgadza się z lokalnym zamówieniem.');
            }
            $locked->update(['status' => 'paid', 'paid_at' => now()]);
            $locked->organization->update([
                'plan_id' => $locked->plan_id,
                'plan_expires_at' => $locked->provider === 'payu'
                    ? (($locked->organization->plan_expires_at?->isFuture() ? $locked->organization->plan_expires_at->copy() : now())->addDays(30))
                    : null,
            ]);
            if ($locked->provider === 'stripe') {
                if (empty($providerData['subscription_id'])) throw new RuntimeException('Brak identyfikatora subskrypcji Stripe.');
                $subscription = Subscription::query()->updateOrCreate(
                    ['provider_subscription_id' => $providerData['subscription_id'] ?? null],
                    [
                        'organization_id' => $locked->organization_id, 'plan_id' => $locked->plan_id,
                        'provider' => 'stripe', 'provider_customer_id' => $providerData['customer_id'] ?? null,
                        'status' => $providerData['status'] ?? 'incomplete',
                        'current_period_end' => $providerData['period_end'] ?? null,
                        'last_event_created_at' => $providerData['event_created_at'] ?? 0,
                        'cancel_at_period_end' => $providerData['cancel_at_period_end'] ?? false,
                    ]
                );
                $locked->organization->update(['current_subscription_id' => $subscription->id]);
            }
        });
    }
}
