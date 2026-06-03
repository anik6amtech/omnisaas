<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ControlPlaneSeeder::class, // operator (/admin)
            PlanSeeder::class,         // SaaS tiers + entitlements
            TenantSeeder::class,       // demo workspace + seller (/app)
        ]);
    }
}
