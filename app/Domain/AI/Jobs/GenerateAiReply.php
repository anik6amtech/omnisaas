<?php

namespace App\Domain\AI\Jobs;

use App\Models\Conversation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

/**
 * Stage 2 of the pipeline (queue: ai). `WithoutOverlapping` per conversation
 * (Redis lock) prevents two messages in one conversation from generating
 * replies concurrently and arriving out of order.
 *
 * Body is implemented in E4: intent classification → hybrid grounding retrieval
 * (catalog facts relational, FAQ/policy via pgvector) → Prism compose with tools
 * → guardrails → SendMessageAction when confident, else escalate to a human.
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

    public function handle(): void
    {
        // E4.
    }
}
