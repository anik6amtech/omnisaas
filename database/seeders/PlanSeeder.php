<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Default SaaS tiers (illustrative pricing from the spec). Operators edit these
 * from the control plane — launching a plan is a panel action, zero deploy.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Free', 'slug' => 'free', 'price_bdt' => 0, 'price_usd' => 0, 'trial_days' => 0, 'sort' => 0,
                'entitlements' => ['limits' => ['channels' => 1, 'ai_replies' => 200, 'seats' => 1, 'products' => 50], 'features' => []]],
            ['name' => 'Starter', 'slug' => 'starter', 'price_bdt' => 1000, 'price_usd' => 10, 'trial_days' => 14, 'sort' => 1,
                'entitlements' => ['limits' => ['channels' => 2, 'ai_replies' => 2000, 'seats' => 1, 'products' => 500], 'features' => []]],
            ['name' => 'Growth', 'slug' => 'growth', 'price_bdt' => 3000, 'price_usd' => 30, 'trial_days' => 14, 'sort' => 2,
                'entitlements' => ['limits' => ['channels' => 3, 'ai_replies' => 10000, 'seats' => 3, 'products' => 5000], 'features' => ['comment_to_dm']]],
            ['name' => 'Business', 'slug' => 'business', 'price_bdt' => 8000, 'price_usd' => 80, 'trial_days' => 14, 'sort' => 3,
                'entitlements' => ['limits' => ['channels' => 3, 'ai_replies' => 40000, 'seats' => 8, 'products' => 50000], 'features' => ['comment_to_dm', 'instagram']]],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
