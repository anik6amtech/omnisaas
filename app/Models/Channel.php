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
 * Meta app credentials (app_secret / verify_token) are per-channel for the
 * "bring your own Meta app" model and fall back to the operator's shared app
 * (config/services.php) when blank — see effectiveAppSecret/effectiveVerifyToken.
 *
 * @property string $id
 * @property string $workspace_id
 * @property ChannelType $type
 * @property string $external_id
 * @property string|null $name
 * @property string|null $access_token
 * @property string|null $app_id
 * @property string|null $app_secret
 * @property string|null $verify_token
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
        'app_id',
        'app_secret',
        'verify_token',
        'token_expires_at',
        'status',
        'settings',
        'meta',
    ];

    /** @var list<string> */
    protected $hidden = ['access_token', 'app_secret', 'verify_token'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ChannelType::class,
            'access_token' => 'encrypted',
            'app_secret' => 'encrypted',
            'verify_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'settings' => 'array',
            'meta' => 'array',
        ];
    }

    /** HMAC app secret for this channel — its own, else the shared app's. */
    public function effectiveAppSecret(): string
    {
        return (string) ($this->app_secret ?: config('services.meta.app_secret'));
    }

    /**
     * Webhook verify token for this channel: an explicit per-channel override if
     * set, otherwise the owning workspace's tenant-wide token, otherwise the
     * platform's shared-app token (.env). Tenants on their own Meta app verify
     * against a token unique to their workspace.
     */
    public function effectiveVerifyToken(): string
    {
        return (string) (
            $this->verify_token
            ?: $this->workspace?->webhook_verify_token
            ?: config('services.meta.webhook_verify_token')
        );
    }
}
