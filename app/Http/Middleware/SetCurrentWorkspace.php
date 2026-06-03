<?php

namespace App\Http\Middleware;

use App\Domain\Tenancy\Context\CurrentWorkspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Binds the active workspace for the tenant plane from the authenticated user's
 * `current_workspace_id`, so the BelongsToWorkspace global scope isolates every
 * query to that tenant. (Octane flushes the scoped CurrentWorkspace per request.)
 */
class SetCurrentWorkspace
{
    public function __construct(private readonly CurrentWorkspace $workspace) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->current_workspace_id !== null) {
            $this->workspace->set($user->current_workspace_id);
        }

        return $next($request);
    }
}
