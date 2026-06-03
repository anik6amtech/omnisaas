<form wire:submit="connect" autocomplete="off" class="space-y-4">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ $idLabel }}</label>
            <input wire:model="external_id" autocomplete="off"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500" />
            @error('external_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Display name</label>
            <input wire:model="name" placeholder="e.g. Sadia's Store" autocomplete="off"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500" />
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Access token</label>
        <input type="password" wire:model="access_token" placeholder="long-lived token" autocomplete="new-password"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500" />
        <p class="mt-1 text-xs text-gray-400">Stored encrypted at rest. (Production: connect via Meta Embedded Signup.)</p>
        @error('access_token') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- Bring your own Meta app (per-tenant credentials) --}}
    <details class="rounded-lg border border-gray-200">
        <summary class="cursor-pointer px-3 py-2 text-sm font-medium text-gray-700">Bring your own Meta app (advanced)</summary>
        <div class="space-y-3 border-t border-gray-200 p-3">
            <p class="text-xs text-gray-500">Connecting your <b>own</b> Meta app? Enter its credentials — stored encrypted, per channel. Leave blank to use the platform's shared app. (Your verify token is issued automatically — see Step 2.)</p>
            <input wire:model="app_id" placeholder="App ID" autocomplete="off"
                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" />
            <input type="password" wire:model="app_secret" placeholder="App secret (HMAC)" autocomplete="new-password"
                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" />
        </div>
    </details>

    <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 font-medium text-white hover:bg-amber-700">Connect {{ $labels[$type] }}</button>
</form>
