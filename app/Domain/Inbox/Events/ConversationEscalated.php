<?php

namespace App\Domain\Inbox\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The AI handed a conversation to a human (low confidence, or an escalation
 * intent like complaint/refund). Surfaces loudly in the inbox.
 */
class ConversationEscalated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public string $reason = 'low_confidence',
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("workspace.{$this->conversation->workspace_id}.inbox"),
            new PrivateChannel("conversation.{$this->conversation->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.escalated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'reason' => $this->reason,
        ];
    }
}
