<?php

namespace App\Domain\Tenancy\Scopes;

use App\Domain\Tenancy\Concerns\BelongsToWorkspace;
use App\Domain\Tenancy\Context\CurrentWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope that constrains every query on a {@see BelongsToWorkspace}
 * model to the active workspace. When no workspace is set (e.g. console, or an
 * operator acting platform-wide) the scope is a no-op — callers that need
 * cross-tenant access must opt in explicitly with `withoutGlobalScope()`.
 */
class WorkspaceScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        /** @var CurrentWorkspace $current */
        $current = app(CurrentWorkspace::class);

        if ($current->check()) {
            $column = method_exists($model, 'getWorkspaceColumn')
                ? $model->getWorkspaceColumn()
                : 'workspace_id';

            $builder->where($model->getTable().'.'.$column, $current->id());
        }
    }
}
