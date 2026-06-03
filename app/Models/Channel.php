<?php

namespace App\Models;

use App\Domain\Channels\Enums\ChannelType;
use App\Domain\Tenancy\Concerns\BelongsToWorkspace;
use Database\Factories\ChannelFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A connected Meta channel (WhatsApp / Instagram / Facebook) for a workspace.
 * The provider `access_token` is encrypted at rest (token vault).
 *
 * @property string $id
 * @property string $workspace_id
 * @property ChannelType $type
 * @property string $external_id
 * @property string|null $name
 * @property string|null $access_token
 * @property Carbon|null $token_expires_at
 * @property string $status
 * @property array<string, mixed>|null $settings
 * @property array<string, mixed>|null $meta
 */
class Channel extends Model
{
    /** @use HasFactory<ChannelFactory> */
    use BelongsToWorkspace, HasFactory, HasUuids;

    protected $fillable = [
        'workspace_id',
        'type',
        'external_id',
        'name',
        'access_token',
        'token_expires_at',
        'status',
        'settings',
        'meta',
    ];

    /** @var list<string> */
    protected $hidden = ['access_token'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ChannelType::class,
            'access_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'settings' => 'array',
            'meta' => 'array',
        ];
    }
}
