<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Free',
                'transaction_limit' => 10,
                'trial_days' => null,
                'price' => 0,
                'is_default' => true,
                'is_active' => true,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'trial_premium'],
            [
                'name' => 'Free Trial Premium',
                'transaction_limit' => null,
                'trial_days' => 7,
                'price' => 0,
                'is_default' => false,
                'is_active' => true,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'premium'],
            [
                'name' => 'Premium',
                'transaction_limit' => null,
                'trial_days' => null,
                'price' => 60000, 
                'is_default' => false,
                'is_active' => true,
            ]
        );
    }
}