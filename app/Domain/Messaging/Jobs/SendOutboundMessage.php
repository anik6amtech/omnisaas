<?php

namespace App\Domain\Messaging\Jobs;

use App\Domain\Channels\ChannelManager;
use App\Domain\Channels\Enums\ChannelType;
use App\Domain\Inbox\Enums\ConversationStatus;
use App\Domain\Messaging\Data\OutboundMessage;
use App\Domain\Messaging\Data\SendResult;
use App\Domain\Messaging\Enums\MessageAuthor;
use App\Domain\Messaging\Events\MessageSent;
use App\Domain\Tenancy\Context\CurrentWorkspace;
use App\Models\Channel;
use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Stage 3 of the pipeline (queue: dispatch). The ONLY component that calls a
 * channel send API. Enforces, in order: opt-out suppression, 24h window
 * legality (AI free-form sends only in-window — the AI never rides the Human
 * Agent tag, so out-of-window AI replies escalate), and a per-channel token
 * bucket (overflow releases the job to retry rather than failing).
 */
class SendOutboundMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [5, 15, 30];

    public function __construct(public Message $message)
    {
        $this->onQueue('dispatch');
    }

    public function handle(CurrentWorkspace $workspace, ChannelManager $channels): void
    {
        $workspace->set($this->message->workspace_id);

        try {
            $conversation = $this->message->conversation()->with(['channel', 'customer'])->first();

            if ($conversation === null) {
                return;
            }

            $channel = $conversation->channel;
            $customer = $conversation->customer;

            if ($customer->opted_out) {
                $this->markFailed('opted_out');

                return;
            }

            if (! $conversation->isWithinWindow()) {
                $this->markFailed('window_closed');

                if ($this->message->author === MessageAuthor::Ai) {
                    $conversation->update(['status' => ConversationStatus::NeedsHuman]);
                }

                return;
            }

            $recipient = $customer->channel_identities[$channel->type->value] ?? null;

            if ($recipient === null) {
                $this->markFailed('no_channel_identity');

                return;
            }

            [$max, $decay] = $this->limitFor($channel);

            $result = RateLimiter::attempt(
                "meta-send:{$channel->id}",
                $max,
                fn (): SendResult => $channels->for($channel)->send($channel, new OutboundMessage(
                    recipientId: (string) $recipient,
                    body: (string) $this->message->body,
                    author: $this->message->author->value,
                )),
                $decay,
            );

            if ($result === false) {
                $this->release(10); // rate limited — degrade gracefully, retry soon

                return;
            }

            if ($result->ok) {
                $this->message->update(['status' => 'sent', 'external_id' => $result->externalMessageId]);
                MessageSent::dispatch($this->message);
            } else {
                $this->markFailed($result->error ?? 'send_failed');
            }
        } finally {
            $workspace->forget();
        }
    }

    /**
     * Per-channel token bucket: [maxAttempts, decaySeconds]. Illustrative;
     * Instagram's ~200/hour tightens in E11.
     *
     * @return array{int, int}
     */
    protected function limitFor(Channel $channel): array
    {
        return match ($channel->type) {
            ChannelType::Instagram => [200, 3600],
            default => [80, 60],
        };
    }

    protected function markFailed(string $reason): void
    {
        $this->message->update([
            'status' => 'failed',
            'meta' => array_merge($this->message->meta ?? [], ['error' => $reason]),
        ]);
    }
}
