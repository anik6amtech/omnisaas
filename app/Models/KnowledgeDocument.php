<?php

namespace App\Models;

use App\Domain\Tenancy\Concerns\BelongsToWorkspace;
use Database\Factories\KnowledgeDocumentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $workspace_id
 * @property string $source_type
 * @property string|null $title
 * @property string $content
 * @property string $status
 * @property Carbon|null $indexed_at
 */
class KnowledgeDocument extends Model
{
    /** @use HasFactory<KnowledgeDocumentFactory> */
    use BelongsToWorkspace, HasFactory, HasUuids;

    protected $fillable = ['workspace_id', 'source_type', 'title', 'content', 'status', 'indexed_at', 'meta'];

    protected $attributes = ['status' => 'pending', 'source_type' => 'faq'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['indexed_at' => 'datetime', 'meta' => 'array'];
    }

    /**
     * @return HasMany<KbChunk, $this>
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(KbChunk::class, 'document_id');
    }
}
