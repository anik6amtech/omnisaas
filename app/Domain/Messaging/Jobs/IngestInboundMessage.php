<?php

namespace App\Domain\Messaging\Jobs;

use App\Domain\AI\Jobs\GenerateAiReply;
use App\Domain\Channels\Data\InboundMessage;
use App\Domain\Inbox\Services\ConversationService;
use App\Domain\Messaging\Enums\MessageAuthor;
use App\Domain\Messaging\Enums\MessageDirection;
use App\Domain\Messaging\Events\MessageReceived;
use App\Domain\Tenancy\Context\CurrentWorkspace;
use App\Models\Channel;
use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Stage 1 of the pipeline (queue: webhooks). Resolves the channel→workspace,
 * merges the customer, opens/refreshes the 24h window, persists the message
 * idempotently, lights up the inbox, and (if AI is on and in-window) hands off
 * to GenerateAiReply.
 */
class IngestInboundMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(public InboundMessage $message)
    {
        $this->onQueue('webhooks');
    }

    public function handle(ConversationService $conversations, CurrentWorkspace $workspace): void
    {
        $channel = Channel::query()
            ->withoutGlobalScopes()
            ->where('type', $this->message->channelType)
            ->where('external_id', $this->message->recipientId)
            ->first();

        if ($channel === null) {
            Log::warning('Inbound webhook for an unknown channel', [
                'type' => $this->message->channelType,
                'recipient' => $this->message->recipientId,
            ]);

            return;
        }

        $workspace->set($channel->workspace_id);

        try {
            // Idempotency — a webhook retry carries the same external id.
            if (Message::query()->where('external_id', $this->message->externalMessageId)->exists()) {
                return;
            }

            $customer = $conversations->resolveCustomer($channel, $this->message->senderId);
            $conversation = $conversations->openOrRefresh($channel, $customer);

            $message = Message::query()->create([
                'workspace_id' => $channel->workspace_id,
                'conversation_id' => $conversation->getKey(),
                'direction' => MessageDirection::In,
                'author' => MessageAuthor::Customer,
                'body' => $this->message->text,
                'external_id' => $this->message->externalMessageId,
                'attachments' => $this->message->attachments ?: null,
                'status' => 'received',
            ]);

            MessageReceived::dispatch($message);

            if ($conversation->ai_enabled
                && $conversation->isWithinWindow()
                && ! $customer->opted_out) {
                GenerateAiReply::dispatch($conversation);
            }
        } finally {
            $workspace->forget();
        }
    }
}
