<?php

use App\Models\Conversation;
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

/** Workspace inbox feed (new conversations / escalations). */
Broadcast::channel('workspace.{workspaceId}.inbox', function (User $user, string $workspaceId) {
    return $user->workspaces()
        ->whereKey($workspaceId)
        ->exists();
});

/** Per-conversation thread — authorized for members of the owning workspace. */
Broadcast::channel('conversation.{conversationId}', function (User $user, string $conversationId) {
    $conversation = Conversation::query()
        ->withoutGlobalScopes()
        ->find($conversationId);

    return $conversation !== null
        && $user->workspaces()->whereKey($conversation->workspace_id)->exists();
});
