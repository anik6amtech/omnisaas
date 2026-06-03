<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty = fake()->numberBetween(1, 3);
        $price = fake()->randomFloat(2, 100, 2000);

        return [
            'order_id' => Order::factory(),
            'name' => fake()->words(2, true),
            'quantity' => $qty,
            'unit_price' => $price,
            'line_total' => $qty * $price,
        ];
    }
}
