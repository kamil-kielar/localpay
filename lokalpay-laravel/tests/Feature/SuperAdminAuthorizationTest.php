<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    public function test_only_super_admin_can_open_platform_api(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/api/admin/overview')->assertForbidden();
        $this->actingAs(User::factory()->superAdmin()->create())->getJson('/api/admin/overview')->assertOk();
    }
}
