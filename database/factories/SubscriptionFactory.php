<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'plan_id' => Plan::factory(),
            'status' => 'active',
            'current_period_ends_at' => now()->addMonth(),
        ];
    }

    public function trialing(): static
    {
        return $this->state(fn (): array => ['status' => 'trialing', 'trial_ends_at' => now()->addDays(14)]);
    }
}
