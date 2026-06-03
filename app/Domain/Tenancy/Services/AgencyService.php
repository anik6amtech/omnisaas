<?php

namespace App\Domain\Tenancy\Services;

use App\Models\UsageEvent;
use App\Models\Workspace;
use Illuminate\Support\Collection;

/**
 * Agency / multi-workspace helpers: list and aggregate across sub-accounts
 * (children of an agency workspace). Per-sub-account billing is just each
 * child's own subscription; the agency view aggregates them.
 */
class AgencyService
{
    /**
     * @return Collection<int, Workspace>
     */
    public function subWorkspaces(Workspace $agency): Collection
    {
        return $agency->children()->withCount('users')->get();
    }

    /** Total AI replies this month across the agency + its sub-accounts. */
    public function aggregateAiReplies(Workspace $agency): int
    {
        $workspaceIds = $agency->children()->pluck('id')->push($agency->getKey());

        return UsageEvent::query()
            ->withoutGlobalScopes()
            ->whereIn('workspace_id', $workspaceIds)
            ->where('type', 'ai_reply')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }
}
