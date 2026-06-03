<?php

namespace App\Models;

use App\Domain\Tenancy\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A vectorized chunk of a knowledge document. The `embedding` (pgvector) column
 * is read/written via raw SQL in the KnowledgeBase service, not an Eloquent
 * cast, so it is excluded from default selects.
 *
 * @property int $id
 * @property string $workspace_id
 * @property string $document_id
 * @property string $content
 */
class KbChunk extends Model
{
    use BelongsToWorkspace;

    public $timestamps = false;

    protected $fillable = ['workspace_id', 'document_id', 'content', 'meta'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<KnowledgeDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }
}
