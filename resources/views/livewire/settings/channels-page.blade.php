<div class="mx-auto max-w-3xl p-6">
    <h1 class="mb-1 text-xl font-semibold text-gray-900">Channels</h1>
    <p class="mb-6 text-sm text-gray-500">Connect your WhatsApp, Instagram, or Facebook Messenger accounts.</p>

    {{-- Connect form --}}
    <form wire:submit="connect" class="mb-8 space-y-4 rounded-xl border border-gray-200 bg-white p-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700">Channel</label>
                <select wire:model.live="type" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                    <option value="whatsapp">WhatsApp</option>
                    <option value="facebook">Facebook Messenger</option>
                    <option value="instagram">Instagram</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Display name</label>
                <input wire:model="name" placeholder="e.g. Sadia's Store"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500" />
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                {{ $type === 'whatsapp' ? 'Phone number ID' : ($type === 'instagram' ? 'Instagram account ID' : 'Page ID') }}
            </label>
            <input wire:model="external_id"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500" />
            @error('external_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Access token</label>
            <input type="password" wire:model="access_token" placeholder="long-lived token"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500" />
            <p class="mt-1 text-xs text-gray-400">Stored encrypted at rest. (Production: connect via Meta Embedded Signup.)</p>
            @error('access_token') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="rounded-lg bg-gray-50 p-3 text-xs text-gray-500">
            Set this as the webhook callback URL in your Meta App:
            <code class="font-mono text-gray-700">{{ $this->webhookUrl($type) }}</code>
        </div>

        <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 font-medium text-white hover:bg-amber-700">Connect channel</button>
    </form>

    {{-- Connected channels --}}
    <div class="space-y-2">
        @forelse ($this->channels as $channel)
            <div wire:key="ch-{{ $channel->id }}" class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-3">
                <div>
                    <p class="font-medium text-gray-900">{{ $channel->name ?? $channel->type->label() }}</p>
                    <p class="text-xs text-gray-500">{{ $channel->type->label() }} · {{ $channel->external_id }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span @class([
                        'rounded-full px-2 py-0.5 text-xs',
                        'bg-green-100 text-green-700' => $channel->status === 'active',
                        'bg-gray-100 text-gray-600' => $channel->status !== 'active',
                    ])>{{ $channel->status }}</span>
                    <button wire:click="disconnect('{{ $channel->id }}')"
                        wire:confirm="Disconnect this channel?"
                        class="text-sm text-red-600 hover:text-red-800">Disconnect</button>
                </div>
            </div>
        @empty
            <p class="rounded-lg border border-gray-200 bg-white p-6 text-center text-sm text-gray-400">No channels connected yet.</p>
        @endforelse
    </div>
</div>
