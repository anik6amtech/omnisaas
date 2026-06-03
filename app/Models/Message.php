<?php

namespace App\Models;

use App\Domain\Messaging\Enums\MessageAuthor;
use App\Domain\Messaging\Enums\MessageDirection;
use App\Domain\Tenancy\Concerns\BelongsToWorkspace;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A canonical in/out message. ULID PK (write-heavy); `external_id` unique for
 * idempotency on webhook retries.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $conversation_id
 * @property MessageDirection $direction
 * @property MessageAuthor $author
 * @property string|null $body
 * @property string|null $lang
 * @property string|null $external_id
 * @property array<int, mixed>|null $attachments
 * @property string $status
 */
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use BelongsToWorkspace, HasFactory, HasUlids;

    protected $fillable = [
        'workspace_id',
        'conversation_id',
        'direction',
        'author',
        'body',
        'lang',
        'external_id',
        'attachments',
        'status',
        'sent_by',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'direction' => MessageDirection::class,
            'author' => MessageAuthor::class,
            'attachments' => 'array',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
