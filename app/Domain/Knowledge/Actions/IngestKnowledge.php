<?php

namespace App\Domain\Knowledge\Actions;

use App\Domain\Knowledge\Jobs\IndexKnowledge;
use App\Models\KnowledgeDocument;

/**
 * Creates (or replaces the content of) a knowledge document and queues it for
 * chunking + embedding. Run with the workspace context set.
 */
class IngestKnowledge
{
    public function execute(string $content, string $sourceType = 'faq', ?string $title = null): KnowledgeDocument
    {
        $document = KnowledgeDocument::query()->create([
            'source_type' => $sourceType,
            'title' => $title,
            'content' => $content,
            'status' => 'pending',
        ]);

        IndexKnowledge::dispatch($document);

        return $document;
    }

    /** Re-index an existing document after an edit. */
    public function reindex(KnowledgeDocument $document): void
    {
        $document->update(['status' => 'pending']);

        IndexKnowledge::dispatch($document);
    }
}
