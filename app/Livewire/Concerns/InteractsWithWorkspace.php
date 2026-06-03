<?php

namespace App\Livewire\Concerns;

use App\Domain\Tenancy\Context\CurrentWorkspace;

/**
 * Binds the active workspace from the authenticated seller on EVERY Livewire
 * request. The route `workspace` middleware only runs on the initial full-page
 * load — Livewire's `/livewire/update` AJAX calls bypass it — so tenant-scoped
 * components must establish the context themselves (boot() runs every request).
 */
trait InteractsWithWorkspace
{
    public function bootInteractsWithWorkspace(): void
    {
        $workspaceId = auth()->user()?->current_workspace_id;

        if ($workspaceId !== null) {
            app(CurrentWorkspace::class)->set($workspaceId);
        }
    }
}
