<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'public_id' => Str::uuid(), 'owner_id' => User::factory(),
            'plan_id' => fn () => Plan::firstOrCreate(['code' => 'free'], ['name' => 'Free', 'price_cents' => 0, 'property_limit' => 3, 'features' => []])->id,
            'name' => fake()->company(), 'slug' => fake()->unique()->slug(2).'-'.Str::lower(Str::random(4)), 'status' => 'active',
        ];
    }
}
