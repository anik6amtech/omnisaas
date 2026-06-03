<?php

namespace App\Domain\Knowledge\Jobs;

use App\Domain\AI\Services\EmbeddingService;
use App\Domain\Knowledge\Services\KnowledgeBase;
use App\Domain\Knowledge\Services\TextChunker;
use App\Domain\Tenancy\Context\CurrentWorkspace;
use App\Models\KnowledgeDocument;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Chunks + embeds a knowledge document into the pgvector store (queue:
 * indexing). Re-runs on edits — existing chunks are replaced.
 */
class IndexKnowledge implements ShouldQueue
{
    use Queueable;

    public function __construct(public KnowledgeDocument $document)
    {
        $this->onQueue('indexing');
    }

    public function handle(
        TextChunker $chunker,
        EmbeddingService $embedder,
        KnowledgeBase $knowledge,
        CurrentWorkspace $workspace,
    ): void {
        $workspace->set($this->document->workspace_id);

        try {
            $this->document->chunks()->delete();

            foreach ($chunker->chunk($this->document->content) as $chunk) {
                $embedding = $embedder->embed($chunk, $this->document->workspace_id);
                $knowledge->store($this->document, $chunk, $embedding);
            }

            $this->document->update([
                'status' => 'indexed',
                'indexed_at' => now(),
            ]);
        } finally {
            $workspace->forget();
        }
    }
}
