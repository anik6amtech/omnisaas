<div>
    <form wire:submit="login" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" wire:model="email" autofocus
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500" />
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" wire:model="password"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500" />
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" wire:model="remember" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500" />
            Remember me
        </label>

        <button type="submit"
            class="w-full rounded-lg bg-amber-600 px-4 py-2 font-medium text-white hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500">
            <span wire:loading.remove wire:target="login">Sign in</span>
            <span wire:loading wire:target="login">Signing in…</span>
        </button>
    </form>
</div>
