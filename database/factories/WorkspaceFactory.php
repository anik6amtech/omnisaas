<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'plan' => 'free',
            'locale' => 'en',
            'timezone' => 'Asia/Dhaka',
            'status' => 'active',
            'data' => null,
        ];
    }

    public function plan(string $plan): static
    {
        return $this->state(fn (): array => ['plan' => $plan]);
    }
}
