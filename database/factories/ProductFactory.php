<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->words(2, true),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 100, 5000),
            'currency' => 'BDT',
            'stock' => fake()->numberBetween(0, 50),
            'active' => true,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (): array => ['stock' => 0]);
    }
}
