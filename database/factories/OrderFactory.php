<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'reference' => 'ORD-'.strtoupper(Str::random(8)),
            'status' => 'new',
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('8801#########'),
            'address' => fake()->address(),
            'delivery_zone' => 'inside_dhaka',
            'subtotal' => 1000,
            'delivery_charge' => 60,
            'total' => 1060,
            'currency' => 'BDT',
            'payment_status' => 'unpaid',
        ];
    }
}
