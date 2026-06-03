<?php

namespace Database\Factories;

use App\Domain\Inbox\Enums\ConversationStatus;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            // Channel + customer share the conversation's workspace.
            'channel_id' => fn (array $attrs) => Channel::factory()->create(['workspace_id' => $attrs['workspace_id']]),
            'customer_id' => fn (array $attrs) => Customer::factory()->create(['workspace_id' => $attrs['workspace_id']]),
            'status' => ConversationStatus::AiHandling,
            'window_expires_at' => now()->addDay(),
            'ai_enabled' => true,
            'last_message_at' => now(),
        ];
    }

    public function windowExpired(): static
    {
        return $this->state(fn (): array => ['window_expires_at' => now()->subHour()]);
    }

    public function needsHuman(): static
    {
        return $this->state(fn (): array => ['status' => ConversationStatus::NeedsHuman]);
    }
}
