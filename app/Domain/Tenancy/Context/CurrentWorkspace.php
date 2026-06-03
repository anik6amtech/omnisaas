<?php

namespace App\Domain\Tenancy\Context;

use App\Models\Workspace;
use App\Providers\AppServiceProvider;

/**
 * Holds the active workspace for the current request or job.
 *
 * Bound as a CONTAINER-SCOPED instance (see {@see AppServiceProvider}),
 * which Octane flushes between requests — so tenant state never leaks across
 * requests/jobs in a long-lived worker. Resolve it via the container
 * (`app(CurrentWorkspace::class)`); never cache it in a singleton.
 */
class CurrentWorkspace
{
    protected ?Workspace $workspace = null;

    public function set(Workspace|string|null $workspace): void
    {
        $this->workspace = $workspace instanceof Workspace
            ? $workspace
            : ($workspace !== null ? Workspace::query()->find($workspace) : null);
    }

    public function get(): ?Workspace
    {
        return $this->workspace;
    }

    public function id(): ?string
    {
        return $this->workspace?->getKey();
    }

    public function check(): bool
    {
        return $this->workspace !== null;
    }

    public function forget(): void
    {
        $this->workspace = null;
    }
}
