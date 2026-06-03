<?php

namespace Database\Factories;

use App\Domain\Messaging\Enums\MessageAuthor;
use App\Domain\Messaging\Enums\MessageDirection;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'conversation_id' => fn (array $attrs) => Conversation::factory()->create(['workspace_id' => $attrs['workspace_id']]),
            'direction' => MessageDirection::In,
            'author' => MessageAuthor::Customer,
            'body' => fake()->sentence(),
            'status' => 'received',
        ];
    }

    public function outbound(): static
    {
        return $this->state(fn (): array => [
            'direction' => MessageDirection::Out,
            'author' => MessageAuthor::Ai,
            'status' => 'sent',
        ]);
    }
}
