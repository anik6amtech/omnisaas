<?php

namespace App\Domain\Knowledge\Services;

use App\Models\KnowledgeDocument;
use Illuminate\Support\Facades\DB;

/**
 * The pgvector store. Writes and reads the vector(1536) column via raw SQL
 * (Eloquent has no vector cast) and ALWAYS scopes retrieval to a workspace —
 * the core tenant-isolation + anti-hallucination guarantee for RAG.
 */
class KnowledgeBase
{
    /**
     * @param  array<int, float>  $embedding
     * @param  array<string, mixed>|null  $meta
     */
    public function store(KnowledgeDocument $document, string $content, array $embedding, ?array $meta = null): void
    {
        DB::insert(
            'INSERT INTO kb_chunks (workspace_id, document_id, content, meta, embedding, created_at)
             VALUES (?, ?, ?, ?, ?::vector, now())',
            [
                $document->workspace_id,
                $document->getKey(),
                $content,
                $meta === null ? null : json_encode($meta),
                $this->toVector($embedding),
            ],
        );
    }

    /**
     * Tenant-scoped cosine ANN search.
     *
     * @param  array<int, float>  $queryEmbedding
     * @return array<int, object>
     */
    public function search(string $workspaceId, array $queryEmbedding, int $limit = 6): array
    {
        $vector = $this->toVector($queryEmbedding);

        return DB::select(
            'SELECT content, (embedding <=> ?::vector) AS distance
             FROM kb_chunks
             WHERE workspace_id = ?
             ORDER BY embedding <=> ?::vector
             LIMIT ?',
            [$vector, $workspaceId, $vector, $limit],
        );
    }

    /**
     * @param  array<int, float>  $embedding
     */
    private function toVector(array $embedding): string
    {
        return '['.implode(',', array_map(static fn ($v): float => (float) $v, $embedding)).']';
    }
}
