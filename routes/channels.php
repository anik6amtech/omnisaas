<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, string $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Private per-workspace channel — authorized only for members of that
 * workspace. The live inbox (E5) extends this family with `workspace.{id}.inbox`
 * and `conversation.{id}`.
 */
Broadcast::channel('workspace.{workspaceId}', function (User $user, string $workspaceId) {
    return $user->workspaces()
        ->whereKey($workspaceId)
        ->exists();
});
