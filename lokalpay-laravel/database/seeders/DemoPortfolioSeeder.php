<?php

namespace Database\Seeders;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Property;
use App\Models\RevenuePayment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoPortfolioSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate(['email' => 'demo@lokalpay.test'], ['name' => 'Anna Kowalska', 'password' => Hash::make(Str::random(40)), 'email_verified_at' => now()]);
        $organization = Organization::query()->firstOrCreate(['slug' => 'demo-lokalpay'], ['owner_id' => $user->id, 'plan_id' => Plan::where('code', 'growth')->value('id'), 'name' => 'Portfel demonstracyjny']);
        Membership::query()->firstOrCreate(['organization_id' => $organization->id, 'user_id' => $user->id], ['role' => 'owner']);
        foreach ([['Studio Mokotów', 42000000], ['Apartament Centrum', 69000000], ['Lokal Gdańsk', 51000000]] as [$name, $cost]) {
            $property = Property::query()->firstOrCreate(['organization_id' => $organization->id, 'name' => $name], ['address' => 'Adres demonstracyjny', 'city' => 'Warszawa', 'purchase_cost_cents' => $cost]);
            for ($month = 1; $month <= 12; $month++) {
                RevenuePayment::query()->firstOrCreate(
                    ['property_id' => $property->id, 'source' => 'demo', 'provider_transaction_id' => "demo-{$property->id}-2025-{$month}"],
                    ['organization_id' => $organization->id, 'amount_cents' => 220000 + ($month * 2500), 'currency' => 'PLN', 'paid_on' => "2025-{$month}-10"]
                );
            }
        }
    }
}
