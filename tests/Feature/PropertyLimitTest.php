<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyLimitTest extends TestCase
{
    use RefreshDatabase;
    public function test_free_plan_rejects_fourth_property(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create(['owner_id' => $user->id]);
        Membership::create(['organization_id' => $org->id, 'user_id' => $user->id, 'role' => 'owner']);
        Property::factory()->count(3)->create(['organization_id' => $org->id]);
        $this->actingAs($user)->withSession(['organization_public_id' => $org->public_id])
            ->postJson('/api/properties', ['name' => 'Czwarty', 'address' => 'Test 1', 'city' => 'Warszawa', 'purchase_cost_cents' => 100])
            ->assertUnprocessable()->assertJsonValidationErrors('plan');
    }
}
