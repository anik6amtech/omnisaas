<?php

namespace App\Domain\AI\Jobs;

use App\Domain\AI\Actions\ClassifyIntent;
use App\Domain\AI\Actions\ComposeReply;
use App\Domain\Inbox\Actions\EscalateConversation;
use App\Domain\Messaging\Actions\SendMessageAction;
use App\Domain\Messaging\Enums\MessageAuthor;
use App\Domain\Messaging\Enums\MessageDirection;
use App\Domain\Tenancy\Context\CurrentWorkspace;
use App\Models\Conversation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

/**
 * Stage 2 of the pipeline (queue: ai). `WithoutOverlapping` per conversation
 * keeps replies ordered. Pipeline: classify intent → guardrails (escalation
 * intent / confidence threshold) → grounded compose → send, else hand off.
 */
class GenerateAiReply implements ShouldQueue
{
    use Queueable;

    public function __construct(public Conversation $conversation)
    {
        $this->onQueue('ai');
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->conversation->id))
                ->releaseAfter(30)
                ->expireAfter(120),
        ];
    }

    public function handle(
        ClassifyIntent $classify,
        ComposeReply $compose,
        SendMessageAction $send,
        EscalateConversation $escalate,
        CurrentWorkspace $workspace,
    ): void {
        $workspace->set($this->conversation->workspace_id);

        try {
            $latest = $this->conversation->messages()
                ->where('direction', MessageDirection::In->value)
                ->latest()
                ->first();

            if ($latest === null || blank($latest->body)) {
                return;
            }

            $intent = $classify->execute((string) $latest->body);

            // Guardrail: escalation intents and low confidence hand off to a human.
            if ($intent->mustEscalate() || $intent->confidence < (float) config('ai.confidence_threshold')) {
                $escalate->execute(
                    $this->conversation,
                    $intent->mustEscalate() ? $intent->intent : 'low_confidence',
                );

                return;
            }

            $reply = $compose->execute($this->conversation, (string) $latest->body);

            if (blank($reply)) {
                $escalate->execute($this->conversation, 'empty_reply');

                return;
            }

            $send->execute($this->conversation, $reply, MessageAuthor::Ai);
        } finally {
            $workspace->forget();
        }
    }
}
