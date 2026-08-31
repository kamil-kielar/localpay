<?php

namespace Tests\Feature;

use App\Models\Lease;
use App\Models\Obligation;
use App\Models\Organization;
use App\Models\Property;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class ObligationAmountIntegrityTest extends TestCase
{
    use RefreshDatabase;
    public function test_checkout_uses_database_remaining_amount_and_reuses_active_order(): void
    {
        $org = Organization::factory()->create();
        $property = Property::factory()->create(['organization_id' => $org->id]);
        $lease = Lease::create(['organization_id' => $org->id, 'property_id' => $property->id, 'tenant_name' => 'Jan', 'starts_at' => now(), 'monthly_rent_cents' => 250000, 'due_day' => 10, 'status' => 'active']);
        $obligation = Obligation::create(['organization_id' => $org->id, 'property_id' => $property->id, 'lease_id' => $lease->id, 'period' => '2026-08', 'due_date' => now(), 'amount_cents' => 250000, 'paid_amount_cents' => 50000, 'currency' => 'PLN', 'status' => 'partial']);
        $this->mock(StripeGateway::class, function (MockInterface $mock): void {
            $mock->once()->shouldReceive('createRentCheckout')->withArgs(fn ($order) => $order->amount_cents === 200000)->andReturn(['id' => 'cs_test', 'url' => 'https://checkout.stripe.com/test', 'expires_at' => now()->addHour()->timestamp]);
        });
        $service = app(CheckoutService::class);
        $first = $service->rent($obligation, 'stripe', null, '127.0.0.1');
        $second = $service->rent($obligation, 'stripe', null, '127.0.0.1');
        $this->assertSame(200000, $first->amount_cents);
        $this->assertSame($first->id, $second->id);
    }
}
