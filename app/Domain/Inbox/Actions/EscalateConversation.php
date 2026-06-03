<?php

namespace App\Domain\Inbox\Actions;

use App\Domain\Inbox\Enums\ConversationStatus;
use App\Domain\Inbox\Events\ConversationEscalated;
use App\Models\Conversation;

/**
 * Hands a conversation to a human: marks it needs_human and notifies the inbox.
 * The single entry point for escalation (AI guardrails, agent action, API).
 */
class EscalateConversation
{
    public function execute(Conversation $conversation, string $reason = 'low_confidence'): void
    {
        $conversation->update(['status' => ConversationStatus::NeedsHuman]);

        ConversationEscalated::dispatch($conversation, $reason);
    }
}
