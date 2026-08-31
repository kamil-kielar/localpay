<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PropertyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'public_id' => Str::uuid(), 'organization_id' => Organization::factory(),
            'name' => 'Lokal '.fake()->unique()->numberBetween(1, 9999), 'address' => fake()->streetAddress(),
            'city' => fake()->city(), 'postal_code' => fake()->postcode(), 'purchase_cost_cents' => fake()->numberBetween(10000000, 100000000),
        ];
    }
}
