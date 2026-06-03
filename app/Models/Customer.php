<?php

namespace App\Models;

use App\Domain\Tenancy\Concerns\BelongsToWorkspace;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A buyer, unified across channels via `channel_identities`.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string|null $name
 * @property string|null $phone
 * @property array<string, string>|null $channel_identities
 * @property bool $opted_out
 */
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use BelongsToWorkspace, HasFactory, HasUuids;

    protected $fillable = [
        'workspace_id',
        'name',
        'phone',
        'channel_identities',
        'opted_out',
        'data',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'opted_out' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel_identities' => 'array',
            'opted_out' => 'boolean',
            'data' => 'array',
        ];
    }

    /**
     * @return HasMany<Conversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
