<?php

namespace App\Domain\AI\Services;

use App\Models\UsageEvent;
use Prism\Prism\Facades\Prism;

/**
 * Wraps Prism embeddings (OpenRouter). Produces vectors at the LOCKED dimension
 * and meters token usage per workspace.
 */
class EmbeddingService
{
    /**
     * @return array<int, float>
     */
    public function embed(string $text, ?string $workspaceId = null): array
    {
        $response = Prism::embeddings()
            ->using(config('ai.provider'), config('ai.models.embedding'))
            ->fromInput($text)
            ->asEmbeddings();

        if ($workspaceId !== null) {
            UsageEvent::query()->create([
                'workspace_id' => $workspaceId,
                'type' => 'embedding',
                'tokens' => $response->usage->tokens ?? 0,
                'model' => config('ai.models.embedding'),
            ]);
        }

        return $response->embeddings[0]->embedding;
    }
}
