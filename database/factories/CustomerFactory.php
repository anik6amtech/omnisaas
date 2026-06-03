<?php

namespace Database\Factories;

use App\Domain\Channels\Enums\ChannelType;
use App\Models\Customer;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->name(),
            'phone' => fake()->numerify('8801#########'),
            'channel_identities' => [
                ChannelType::WhatsApp->value => fake()->numerify('8801#########'),
            ],
            'opted_out' => false,
        ];
    }

    public function optedOut(): static
    {
        return $this->state(fn (): array => ['opted_out' => true]);
    }
}
