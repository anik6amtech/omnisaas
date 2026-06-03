<?php

namespace App\Domain\Tenancy\Concerns;

use App\Domain\Tenancy\Context\CurrentWorkspace;
use App\Domain\Tenancy\Scopes\WorkspaceScope;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Apply to every tenant-scoped model. Adds the {@see WorkspaceScope} global
 * scope (so reads are tenant-isolated by default), auto-fills `workspace_id`
 * from the active workspace on create, and exposes the `workspace` relation.
 *
 * Tenant isolation is therefore the DEFAULT and cross-tenant access the
 * explicit exception (`Model::withoutGlobalScope(WorkspaceScope::class)`).
 * Postgres RLS is planned as defense-in-depth (see workspaces migration TODO).
 *
 * @phpstan-require-extends Model
 */
trait BelongsToWorkspace
{
    public static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope(new WorkspaceScope);

        static::creating(function ($model): void {
            $column = $model->getWorkspaceColumn();

            if (empty($model->getAttribute($column))) {
                $model->setAttribute($column, app(CurrentWorkspace::class)->id());
            }
        });
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, $this->getWorkspaceColumn());
    }

    public function getWorkspaceColumn(): string
    {
        return 'workspace_id';
    }
}
