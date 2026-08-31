<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('lokalpay.plans') as $code => $data) {
            Plan::query()->updateOrCreate(['code' => $code], $data + ['active' => true]);
        }
    }
}
