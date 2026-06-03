<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\Enums\MessageAuthor;
use App\Domain\Messaging\Enums\MessageDirection;
use App\Domain\Messaging\Jobs\SendOutboundMessage;
use App\Models\Conversation;
use App\Models\Message;

/**
 * The single entry point for sending an outbound message — used by the AI
 * pipeline, the inbox (agent), and the API, so behaviour stays identical
 * everywhere. Persists a queued message and hands the actual send to the
 * rate-limited, window-checked dispatcher.
 */
class SendMessageAction
{
    public function execute(
        Conversation $conversation,
        string $body,
        MessageAuthor $author = MessageAuthor::Ai,
        ?int $sentBy = null,
    ): Message {
        $message = Message::query()->create([
            'workspace_id' => $conversation->workspace_id,
            'conversation_id' => $conversation->getKey(),
            'direction' => MessageDirection::Out,
            'author' => $author,
            'body' => $body,
            'status' => 'queued',
            'sent_by' => $sentBy,
        ]);

        SendOutboundMessage::dispatch($message);

        return $message;
    }
}
