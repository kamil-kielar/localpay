<?php

namespace Tests\Feature;

use App\Models\Lease;
use App\Models\Organization;
use App\Models\Property;
use App\Models\TenantInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvitationAccessTest extends TestCase
{
    use RefreshDatabase;
    public function test_invitation_requires_valid_signed_url_and_matching_token(): void
    {
        $org = Organization::factory()->create();
        $property = Property::factory()->create(['organization_id' => $org->id]);
        $lease = Lease::create(['organization_id' => $org->id, 'property_id' => $property->id, 'tenant_name' => 'Jan', 'tenant_email' => 'jan@example.test', 'starts_at' => now(), 'monthly_rent_cents' => 200000, 'due_day' => 10, 'status' => 'draft']);
        $raw = Str::random(64);
        $invitation = TenantInvitation::create(['organization_id' => $org->id, 'lease_id' => $lease->id, 'invited_by' => $org->owner_id, 'email' => 'jan@example.test', 'token_hash' => hash('sha256', $raw), 'expires_at' => now()->addDay()]);
        $url = URL::temporarySignedRoute('tenant.invitation.show', now()->addHour(), ['invitation' => $invitation, 'token' => $raw]);
        $this->get($url)->assertOk();
        $this->get(str_replace($raw, 'invalid', $url))->assertForbidden();
    }
}
