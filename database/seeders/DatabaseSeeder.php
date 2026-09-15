<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Admin::updateOrCreate(['email' => 'admin@ppf.com'], [
            'name' => 'Proflect Administrator',
            'password' => 'admin123',
        ]);

        Plan::updateOrCreate(['slug' => 'free'], [
            'name' => 'Free Plan', 'description' => 'Try Proflect protection free for 15 days.', 'price' => 0,
            'currency' => 'USD', 'duration_years' => 0, 'duration_days' => 15, 'coverage_sqm' => 5,
            'features' => ['15 days of protection', 'No payment required', 'Cancel automatically at trial end'],
            'accent' => 'black', 'is_active' => true, 'sort_order' => 0,
        ]);

        Plan::updateOrCreate(['slug' => 'silver'], [
            'name' => 'Silver Plan', 'description' => 'Essential protection for everyday driving.', 'price' => 54900,
            'currency' => 'USD', 'duration_years' => 2, 'coverage_sqm' => 5, 'features' => ['Accidental damage cover', 'Film + labour included', 'No excess or callout fee'],
            'accent' => 'silver', 'is_active' => true, 'sort_order' => 1,
        ]);
        Plan::updateOrCreate(['slug' => 'gold'], [
            'name' => 'Gold Plan', 'description' => 'Complete long-term protection and peace of mind.', 'price' => 89900,
            'currency' => 'USD', 'duration_years' => 5, 'coverage_sqm' => 5, 'features' => ['Accidental damage cover', 'Film + labour included', 'No excess or callout fee', 'Priority claim support'],
            'accent' => 'gold', 'is_active' => true, 'sort_order' => 2,
        ]);
    }
}
