<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'price_bdt' => 1000,
            'price_usd' => 10,
            'billing_cycle' => 'monthly',
            'trial_days' => 0,
            'entitlements' => [
                'limits' => ['channels' => 2, 'ai_replies' => 2000, 'seats' => 1, 'products' => 100],
                'features' => ['comment_to_dm'],
            ],
            'active' => true,
        ];
    }

    public function withTrial(int $days = 14): static
    {
        return $this->state(fn (): array => ['trial_days' => $days]);
    }

    /**
     * @param  array<string, int>  $limits
     */
    public function limits(array $limits): static
    {
        return $this->state(fn (array $attrs): array => [
            'entitlements' => array_replace_recursive($attrs['entitlements'] ?? [], ['limits' => $limits]),
        ]);
    }
}
