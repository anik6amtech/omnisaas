<div class="mx-auto max-w-4xl p-6">
    <h1 class="mb-4 text-xl font-semibold text-gray-900">Knowledge base</h1>

    <form wire:submit="ingest" class="mb-6 space-y-3 rounded-xl border border-gray-200 bg-white p-4">
        <div class="flex gap-3">
            <input wire:model="title" placeholder="Title (optional)"
                class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500" />
            <select wire:model="sourceType" class="rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                <option value="faq">FAQ</option>
                <option value="policy">Policy</option>
            </select>
        </div>
        <textarea wire:model="content" rows="4" placeholder="Paste an FAQ answer or policy…"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
        @error('content') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 font-medium text-white hover:bg-amber-700">Add &amp; index</button>
    </form>

    <div class="space-y-2">
        @forelse ($this->documents as $document)
            <div wire:key="d-{{ $document->id }}" class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-3">
                <div class="min-w-0">
                    <p class="truncate font-medium text-gray-900">{{ $document->title ?? Str::limit($document->content, 60) }}</p>
                    <p class="text-xs uppercase tracking-wide text-gray-400">{{ $document->source_type }}</p>
                </div>
                <span @class([
                    'rounded-full px-2 py-0.5 text-xs font-medium',
                    'bg-green-100 text-green-700' => $document->status === 'indexed',
                    'bg-amber-100 text-amber-700' => $document->status === 'pending',
                    'bg-red-100 text-red-700' => $document->status === 'failed',
                ])>{{ $document->status }}</span>
            </div>
        @empty
            <p class="rounded-lg border border-gray-200 bg-white p-6 text-center text-sm text-gray-400">No documents yet.</p>
        @endforelse
    </div>
</div>
