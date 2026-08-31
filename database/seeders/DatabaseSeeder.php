<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PlanSeeder::class);
        if (app()->environment(['local', 'testing']) && config('lokalpay.demo_seed')) {
            $this->call(DemoPortfolioSeeder::class);
        }
    }
}
