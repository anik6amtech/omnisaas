@php
    $labels = ['whatsapp' => 'WhatsApp', 'facebook' => 'Facebook Messenger', 'instagram' => 'Instagram'];
    $idLabel = $type === 'whatsapp' ? 'Phone number ID' : ($type === 'instagram' ? 'Instagram account ID' : 'Page ID');
@endphp

<div class="mx-auto max-w-3xl p-6">
    <h1 class="mb-1 text-xl font-semibold text-gray-900">Channels</h1>
    <p class="mb-6 text-sm text-gray-500">Connect your WhatsApp, Instagram, or Facebook Messenger accounts.</p>

    {{-- Channel tabs --}}
    <div class="mb-6 flex gap-1 rounded-xl border border-gray-200 bg-gray-50 p-1">
        @foreach ($labels as $value => $label)
            <button type="button" wire:click="$set('type', '{{ $value }}')"
                @class([
                    'flex-1 rounded-lg px-3 py-2 text-sm font-medium transition',
                    'bg-white text-amber-700 shadow-sm ring-1 ring-amber-200' => $type === $value,
                    'text-gray-500 hover:text-gray-700' => $type !== $value,
                ])>{{ $label }}</button>
        @endforeach
    </div>

    {{-- Connect form (fields adapt to the active tab) --}}
    <form wire:submit="connect" autocomplete="off" class="mb-6 space-y-4 rounded-xl border border-gray-200 bg-white p-5">
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
                <p class="text-xs text-gray-500">Connecting your <b>own</b> Meta app? Enter its credentials — stored encrypted, per channel. Leave blank to use the platform's shared app. (The verify token is issued for you — see below.)</p>
                <input wire:model="app_id" placeholder="App ID" autocomplete="off"
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" />
                <input type="password" wire:model="app_secret" placeholder="App secret (HMAC)" autocomplete="new-password"
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" />
            </div>
        </details>

        <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 font-medium text-white hover:bg-amber-700">Connect {{ $labels[$type] }}</button>
    </form>

    {{-- Webhook configuration: the tenant-wise verify token --}}
    <div class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50/60 p-5">
        <h2 class="mb-1 text-sm font-semibold text-indigo-800">Webhook configuration</h2>
        <p class="mb-3 text-xs text-indigo-700/80">Paste this <b>Verify token</b> into your Meta app's webhook config. It's unique to your workspace and the same for all your channels. Your channel's <b>Callback URL</b> appears below once connected.</p>
        <label class="block text-xs font-medium text-gray-600">Verify token (yours)</label>
        <input type="text" readonly value="{{ $this->verifyToken() }}" onfocus="this.select()"
            class="mt-1 block w-full rounded-lg border-indigo-200 bg-white font-mono text-sm text-gray-800 shadow-sm focus:border-indigo-400 focus:ring-indigo-400" />
    </div>

    {{-- Per-channel setup guide (reactive to the active tab) --}}
    <div class="mb-8 rounded-xl border border-amber-200 bg-amber-50/60 p-5">
        <h2 class="mb-3 text-sm font-semibold text-amber-800">Setup guide — {{ $labels[$type] }}</h2>
        <ol class="list-decimal space-y-2 pl-5 text-sm text-gray-700 [&_code]:rounded [&_code]:bg-amber-100 [&_code]:px-1 [&_code]:py-0.5 [&_code]:font-mono [&_code]:text-xs">
            @foreach ($this->setupSteps as $step)
                <li wire:key="step-{{ $type }}-{{ $loop->index }}">{!! $step !!}</li>
            @endforeach
        </ol>
        <p class="mt-3 text-xs text-amber-700/80">
            Start <b>Meta Business Verification + App Review</b> early — it gates production, not your code.
            Hosted plans connect in one click via Meta Embedded Signup (coming soon); this manual form is for self-hosted / dev.
        </p>
    </div>

    {{-- Connected channels --}}
    <h2 class="mb-2 text-sm font-semibold text-gray-700">Connected channels</h2>
    <div class="space-y-2">
        @forelse ($this->channels as $channel)
            <div wire:key="ch-{{ $channel->id }}" class="rounded-lg border border-gray-200 bg-white p-3">
                <div class="flex items-center justify-between">
                    <div class="min-w-0">
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
                <div class="mt-2 border-t border-gray-100 pt-2">
                    <label class="block text-xs font-medium text-gray-500">Callback URL</label>
                    <input type="text" readonly value="{{ $this->channelWebhookUrl($channel) }}" onfocus="this.select()"
                        class="mt-0.5 block w-full rounded-md border-gray-200 bg-gray-50 font-mono text-xs text-gray-700" />
                </div>
            </div>
        @empty
            <p class="rounded-lg border border-gray-200 bg-white p-6 text-center text-sm text-gray-400">No channels connected yet.</p>
        @endforelse
    </div>
</div>
