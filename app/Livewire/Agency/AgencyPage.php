<?php

namespace App\Livewire\Agency;

use App\Domain\Tenancy\Actions\SwitchWorkspace;
use App\Domain\Tenancy\Services\AgencyService;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Agency cockpit: list sub-accounts (children of the active workspace) and drop
 * into any of them. Each sub-account carries its own subscription/billing.
 */
#[Layout('components.layouts.app')]
class AgencyPage extends Component
{
    public function agency(): ?Workspace
    {
        return auth()->user()?->currentWorkspace;
    }

    /**
     * @return Collection<int, Workspace>
     */
    #[Computed]
    public function subWorkspaces(): Collection
    {
        $agency = $this->agency();

        return $agency !== null ? app(AgencyService::class)->subWorkspaces($agency) : collect();
    }

    public function switchTo(string $workspaceId, SwitchWorkspace $switch)
    {
        $switch->execute(auth()->user(), Workspace::query()->findOrFail($workspaceId));

        return $this->redirect(route('app.inbox'), navigate: true);
    }

    public function render()
    {
        return view('livewire.agency.agency-page');
    }
}
