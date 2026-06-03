<?php

namespace App\Domain\AI\Actions;

use App\Domain\AI\Services\EmbeddingService;
use App\Domain\Knowledge\Services\KnowledgeBase;
use App\Models\Conversation;
use App\Models\UsageEvent;
use Illuminate\Support\Arr;
use Prism\Prism\Facades\Prism;

/**
 * Composes a grounded reply: embed the query, retrieve tenant-scoped KB context,
 * and generate with the strong model under a strict no-hallucination system
 * prompt. Catalog facts (price/stock) are read relationally upstream — never
 * from vectors. Tool-calling (create_order, payment_link, …) layers in at E6/E7.
 */
class ComposeReply
{
    public function __construct(
        private readonly EmbeddingService $embedder,
        private readonly KnowledgeBase $knowledge,
    ) {}

    public function execute(Conversation $conversation, string $message): string
    {
        $embedding = $this->embedder->embed($message, $conversation->workspace_id);
        $chunks = $this->knowledge->search(
            $conversation->workspace_id,
            $embedding,
            (int) config('ai.retrieval_limit', 6),
        );

        $context = collect($chunks)
            ->map(static fn (object $row): string => (string) Arr::get((array) $row, 'content'))
            ->implode("\n---\n");

        $response = Prism::text()
            ->using(config('ai.provider'), config('ai.models.generation'))
            ->withSystemPrompt($this->systemPrompt($context))
            ->withPrompt($message)
            ->asText();

        UsageEvent::query()->create([
            'workspace_id' => $conversation->workspace_id,
            'type' => 'ai_reply',
            'tokens' => $response->usage->promptTokens + $response->usage->completionTokens,
            'model' => config('ai.models.generation'),
        ]);

        return trim($response->text);
    }

    private function systemPrompt(string $context): string
    {
        return <<<PROMPT
            You are a friendly sales assistant for a small f-commerce shop. Answer
            ONLY from the context below. If the answer is not in the context, say you
            will check and a human will follow up — never invent prices, stock, or
            policies. Reply in the customer's own language (Bangla, Banglish, or
            English). Be concise and warm.

            Context:
            {$context}
            PROMPT;
    }
}
