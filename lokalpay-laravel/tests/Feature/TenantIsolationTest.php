<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;
    public function test_member_cannot_update_another_organizations_property(): void
    {
        $user = User::factory()->create();
        $own = Organization::factory()->create(['owner_id' => $user->id]);
        Membership::create(['organization_id' => $own->id, 'user_id' => $user->id, 'role' => 'owner']);
        $foreign = Organization::factory()->create();
        $property = Property::factory()->create(['organization_id' => $foreign->id]);
        $this->actingAs($user)->withSession(['organization_public_id' => $own->public_id])
            ->putJson("/api/properties/{$property->public_id}", ['name' => 'Atak', 'address' => 'X', 'city' => 'Y', 'purchase_cost_cents' => 1])
            ->assertNotFound();
        $this->assertDatabaseMissing('properties', ['id' => $property->id, 'name' => 'Atak']);
    }
}
