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

        Plan::updateOrCreate(['slug' => 'silver'], [
            'name' => 'Silver Plan', 'description' => 'Essential protection for everyday driving.', 'price' => 54900,
            'currency' => 'INR', 'duration_years' => 2, 'coverage_sqm' => 5, 'features' => ['Accidental damage cover', 'Film + labour included', 'No excess or callout fee'],
            'accent' => 'silver', 'is_active' => true, 'sort_order' => 1,
        ]);
        Plan::updateOrCreate(['slug' => 'gold'], [
            'name' => 'Gold Plan', 'description' => 'Complete long-term protection and peace of mind.', 'price' => 89900,
            'currency' => 'INR', 'duration_years' => 5, 'coverage_sqm' => 5, 'features' => ['Accidental damage cover', 'Film + labour included', 'No excess or callout fee', 'Priority claim support'],
            'accent' => 'gold', 'is_active' => true, 'sort_order' => 2,
        ]);
    }
}
