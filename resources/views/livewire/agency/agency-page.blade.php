<div class="mx-auto max-w-4xl p-6">
    <h1 class="mb-1 text-xl font-semibold text-gray-900">Agency</h1>
    <p class="mb-4 text-sm text-gray-500">Sub-accounts managed under {{ $this->agency()?->name }}.</p>

    <div class="space-y-2">
        @forelse ($this->subWorkspaces as $workspace)
            <div wire:key="ws-{{ $workspace->id }}" class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-3">
                <div>
                    <p class="font-medium text-gray-900">{{ $workspace->name }}</p>
                    <p class="text-xs text-gray-500">{{ ucfirst($workspace->plan) }} · {{ $workspace->users_count }} members</p>
                </div>
                <button wire:click="switchTo('{{ $workspace->id }}')"
                    class="rounded-lg bg-amber-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-amber-700">Manage</button>
            </div>
        @empty
            <p class="rounded-lg border border-gray-200 bg-white p-6 text-center text-sm text-gray-400">
                No sub-accounts. This workspace isn't an agency yet.
            </p>
        @endforelse
    </div>
</div>
