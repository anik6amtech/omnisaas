<?php

namespace App\Domain\Messaging\Jobs;

use App\Domain\Channels\Data\InboundMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * First stage of the async pipeline (queue: webhooks). Webhooks are never
 * processed inline — the controller verifies + enqueues + returns 200.
 *
 * Body is implemented in E3: resolve the channel by (type, recipientId), upsert
 * the customer, open/refresh the 24h conversation window, persist the Message
 * idempotently (unique external_id), broadcast to the inbox, and dispatch
 * GenerateAiReply when AI is enabled and the conversation is in-window.
 */
class IngestInboundMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(public InboundMessage $message)
    {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        // E3.
    }
}
