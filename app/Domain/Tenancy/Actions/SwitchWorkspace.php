<?php

namespace App\Domain\Tenancy\Actions;

use App\Models\User;
use App\Models\Workspace;

/**
 * Switches a user's active workspace. Authorized if the user is a member, OR
 * (agency) a member of the workspace's parent — letting an agency operator drop
 * into any of its sub-accounts.
 */
class SwitchWorkspace
{
    public function execute(User $user, Workspace $workspace): void
    {
        $isMember = $user->workspaces()->whereKey($workspace->getKey())->exists();

        $isAgencyParentMember = $workspace->parent_id !== null
            && $user->workspaces()->whereKey($workspace->parent_id)->exists();

        abort_unless($isMember || $isAgencyParentMember, 403);

        $user->update(['current_workspace_id' => $workspace->getKey()]);
    }
}
