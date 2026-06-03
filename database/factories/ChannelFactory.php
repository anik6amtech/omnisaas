<?php

namespace Database\Factories;

use App\Domain\Channels\Enums\ChannelType;
use App\Models\Channel;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Channel>
 */
class ChannelFactory extends Factory
{
    protected $model = Channel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'type' => ChannelType::WhatsApp,
            'external_id' => (string) fake()->unique()->numerify('###############'),
            'name' => fake()->company().' WhatsApp',
            'access_token' => 'test-token-'.fake()->uuid(),
            'status' => 'active',
        ];
    }

    public function whatsapp(): static
    {
        return $this->state(fn (): array => ['type' => ChannelType::WhatsApp]);
    }

    public function messenger(): static
    {
        return $this->state(fn (): array => ['type' => ChannelType::Facebook]);
    }
}
