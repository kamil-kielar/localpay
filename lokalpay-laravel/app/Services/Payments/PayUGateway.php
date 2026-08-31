<?php

namespace App\Services\Payments;

use App\Models\Obligation;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\RentOrder;
use App\Models\SaasOrder;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayUGateway
{
    private function baseUrl(): string
    {
        return config('lokalpay.payu.environment') === 'production'
            ? 'https://secure.payu.com' : 'https://secure.snd.payu.com';
    }

    private function token(): string
    {
        $response = Http::asForm()->post($this->baseUrl().'/pl/standard/user/oauth/authorize', [
            'grant_type' => 'client_credentials',
            'client_id' => config('lokalpay.payu.client_id'),
            'client_secret' => config('lokalpay.payu.client_secret'),
        ])->throw();
        $token = (string) $response->json('access_token');
        if ($token === '') throw new RuntimeException('PayU nie zwróciło tokenu.');
        return $token;
    }

    public function createRentOrder(RentOrder $order, Obligation $obligation, string $customerIp): array
    {
        return $this->createOrder($order->idempotency_key, [
            'description' => "Czynsz {$obligation->period} — {$obligation->property->name}",
            'amount' => $order->amount_cents,
            'email' => $obligation->lease->tenant_email,
            'continueUrl' => route('tenant.portal', ['payment' => 'success']),
            'notifyUrl' => route('webhooks.payu'),
            'customerIp' => $customerIp,
            'products' => [['name' => "Czynsz {$obligation->period}", 'unitPrice' => (string) $order->amount_cents, 'quantity' => '1']],
        ]);
    }

    public function createPlanOrder(SaasOrder $order, Organization $organization, Plan $plan, string $customerIp): array
    {
        return $this->createOrder($order->idempotency_key, [
            'description' => "LokalPay {$plan->name} — dostęp 30 dni (bez odnowienia)",
            'amount' => $order->amount_cents,
            'email' => $organization->billing_email ?: $organization->owner->email,
            'continueUrl' => route('billing.return', ['status' => 'success']),
            'notifyUrl' => route('webhooks.payu'),
            'customerIp' => $customerIp,
            'products' => [['name' => "Plan {$plan->name} — 30 dni", 'unitPrice' => (string) $order->amount_cents, 'quantity' => '1']],
        ]);
    }

    private function createOrder(string $externalId, array $data): array
    {
        foreach (['pos_id', 'client_id', 'client_secret', 'second_key'] as $key) {
            if (!(string) config("lokalpay.payu.{$key}")) throw new RuntimeException('PayU nie jest skonfigurowane.');
        }
        $response = Http::withToken($this->token())->withoutRedirecting()->post($this->baseUrl().'/api/v2_1/orders', [
            'notifyUrl' => $data['notifyUrl'],
            'continueUrl' => $data['continueUrl'],
            'customerIp' => $data['customerIp'],
            'merchantPosId' => (string) config('lokalpay.payu.pos_id'),
            'description' => $data['description'],
            'currencyCode' => 'PLN',
            'totalAmount' => (string) $data['amount'],
            'extOrderId' => $externalId,
            'buyer' => ['email' => $data['email'] ?: null, 'language' => 'pl'],
            'products' => $data['products'],
        ]);
        if (!in_array($response->status(), [200, 201, 302], true)) $response->throw();
        $url = (string) $response->json('redirectUri');
        $id = (string) $response->json('orderId');
        if ($url === '' || $id === '') throw new RuntimeException('PayU nie zwróciło zamówienia.');
        return ['id' => $id, 'url' => $url, 'expires_at' => now()->addHour()->timestamp];
    }

    public function verifySignature(string $payload, string $header): bool
    {
        $parts = [];
        foreach (explode(';', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            $parts[strtolower($key)] = $value;
        }
        $algorithm = strtolower($parts['algorithm'] ?? 'md5');
        if ($algorithm !== 'md5' || empty($parts['signature'])) return false;
        $expected = md5($payload.(string) config('lokalpay.payu.second_key'));
        return hash_equals($expected, strtolower($parts['signature']));
    }
}
