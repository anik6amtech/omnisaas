<?php

namespace App\Models;

use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * A workspace = a tenant. Root of the shared-DB multi-tenant model; every
 * tenant-scoped table FKs back to here via `workspace_id`. The workspace itself
 * is NOT workspace-scoped, so it does not use the BelongsToWorkspace trait.
 *
 * A workspace may have a `parent` (agency) and own children (sub-accounts), plus
 * its own white-label branding + custom domain.
 *
 * @property string $id
 * @property string|null $parent_id
 * @property string $name
 * @property string $plan
 * @property string|null $custom_domain
 * @property array<string, mixed>|null $branding
 * @property string|null $webhook_verify_token
 */
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'parent_id', 'name', 'slug', 'plan', 'locale', 'timezone',
        'status', 'data', 'custom_domain', 'branding',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['data' => 'array', 'branding' => 'array'];
    }

    public function isAgency(): bool
    {
        return $this->children()->exists();
    }

    public function brandName(): string
    {
        return (string) Arr::get($this->branding ?? [], 'name', 'OmniReply');
    }

    public function brandColor(): string
    {
        return (string) Arr::get($this->branding ?? [], 'color', 'amber');
    }

    /**
     * This tenant's webhook verify token — stable, unique per workspace, and
     * generated on first read. The seller pastes it into their own Meta app's
     * webhook config; {@see Channel::effectiveVerifyToken()} falls back to it.
     */
    public function webhookVerifyToken(): string
    {
        if ($this->webhook_verify_token === null) {
            $this->webhook_verify_token = 'omr_'.Str::random(32);
            $this->save();
        }

        return $this->webhook_verify_token;
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'parent_id');
    }

    /**
     * @return HasMany<Workspace, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Workspace::class, 'parent_id');
    }

    /**
     * Member users with their tenant-level role.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->withPivot('role')
            ->withTimestamps();
    }
}
