<?php

namespace App\Models;

use App\Domain\Inbox\Enums\ConversationStatus;
use App\Domain\Tenancy\Concerns\BelongsToWorkspace;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A customer conversation on a channel, with its 24-hour service window,
 * status, AI toggle, and assignment.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $channel_id
 * @property string $customer_id
 * @property ConversationStatus $status
 * @property Carbon|null $window_expires_at
 * @property bool $ai_enabled
 * @property int|null $assigned_to
 * @property Carbon|null $last_message_at
 */
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use BelongsToWorkspace, HasFactory, HasUuids;

    protected $fillable = [
        'workspace_id',
        'channel_id',
        'customer_id',
        'status',
        'window_expires_at',
        'ai_enabled',
        'assigned_to',
        'last_message_at',
        'data',
    ];

    /** Mirror DB column defaults so they're present on freshly-created models. */
    protected $attributes = [
        'status' => 'ai_handling',
        'ai_enabled' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ConversationStatus::class,
            'window_expires_at' => 'datetime',
            'last_message_at' => 'datetime',
            'ai_enabled' => 'boolean',
            'data' => 'array',
        ];
    }

    /** Is a free reply currently allowed (inside Meta's 24h window)? */
    public function isWithinWindow(): bool
    {
        return $this->window_expires_at !== null && $this->window_expires_at->isFuture();
    }

    /**
     * @return BelongsTo<Channel, $this>
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
