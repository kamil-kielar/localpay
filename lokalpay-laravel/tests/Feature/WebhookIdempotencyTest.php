<?php

namespace Tests\Feature;

use App\Services\Payments\WebhookProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;
    public function test_provider_event_is_reserved_once(): void
    {
        $processor = app(WebhookProcessor::class);
        $this->assertNotNull($processor->reserve('stripe', 'evt_123', 'checkout.session.completed', '{}'));
        $this->assertNull($processor->reserve('stripe', 'evt_123', 'checkout.session.completed', '{}'));
        $this->assertDatabaseCount('webhook_events', 1);
    }
}
