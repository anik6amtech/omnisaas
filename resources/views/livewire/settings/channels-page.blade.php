@php
    $labels = ['whatsapp' => 'WhatsApp', 'facebook' => 'Facebook Messenger', 'instagram' => 'Instagram'];
    $idLabel = $type === 'whatsapp' ? 'Phone number ID' : ($type === 'instagram' ? 'Instagram account ID' : 'Page ID');
    $subscribeFields = ['whatsapp' => 'messages', 'facebook' => 'messages, messaging_postbacks', 'instagram' => 'messages, comments'][$type];
    $typeChannels = $this->channels->filter(fn ($c) => $c->type->value === $type);
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

    {{-- Step 1 — Connect the account --}}
    <section class="mb-5 rounded-xl border border-gray-200 bg-white p-5">
        <div class="mb-4 flex items-center gap-2">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-600 text-xs font-semibold text-white">1</span>
            <h2 class="text-sm font-semibold text-gray-900">Connect your {{ $labels[$type] }} account</h2>
        </div>

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
    </section>

    {{-- Step 2 — Point Meta's webhook here (Callback URL + Verify token together) --}}
    <section class="mb-5 rounded-xl border border-indigo-200 bg-indigo-50/50 p-5">
        <div class="mb-1 flex items-center gap-2">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-xs font-semibold text-white">2</span>
            <h2 class="text-sm font-semibold text-gray-900">Point Meta's webhook here</h2>
        </div>
        <p class="mb-4 pl-8 text-xs text-indigo-700/80">Paste these into your Meta app's webhook config, then subscribe to <code class="rounded bg-indigo-100 px-1 py-0.5 font-mono">{{ $subscribeFields }}</code>.</p>

        <div class="space-y-4 pl-8">
            {{-- Callback URL: one per connected channel of this type; appears after Step 1 --}}
            <div>
                <label class="block text-xs font-medium text-gray-600">Callback URL</label>
                @forelse ($typeChannels as $channel)
                    <div wire:key="cb-{{ $channel->id }}" class="mt-1">
                        <input type="text" readonly value="{{ $this->channelWebhookUrl($channel) }}" onfocus="this.select()"
                            class="block w-full rounded-lg border-indigo-200 bg-white font-mono text-sm text-gray-800 shadow-sm focus:border-indigo-400 focus:ring-indigo-400" />
                        @if ($typeChannels->count() > 1)
                            <span class="text-[11px] text-gray-400">for {{ $channel->name ?? $channel->external_id }}</span>
                        @endif
                    </div>
                @empty
                    <p class="mt-1 rounded-lg border border-dashed border-indigo-200 bg-white/60 px-3 py-2 text-xs text-gray-400">
                        Connect your {{ $labels[$type] }} account in Step 1 — your Callback URL appears here once connected.
                    </p>
                @endforelse
            </div>

            {{-- Verify token: tenant-wide, same for every channel --}}
            <div>
                <label class="block text-xs font-medium text-gray-600">Verify token <span class="font-normal text-gray-400">— unique to your workspace</span></label>
                <input type="text" readonly value="{{ $this->verifyToken() }}" onfocus="this.select()"
                    class="mt-1 block w-full rounded-lg border-indigo-200 bg-white font-mono text-sm text-gray-800 shadow-sm focus:border-indigo-400 focus:ring-indigo-400" />
            </div>
        </div>
    </section>

    {{-- Full step-by-step guide (collapsible reference, reactive to the active tab) --}}
    <details class="mb-8 rounded-xl border border-amber-200 bg-amber-50/60 p-5" open>
        <summary class="cursor-pointer text-sm font-semibold text-amber-800">Full setup guide — {{ $labels[$type] }}</summary>
        <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-gray-700 [&_code]:rounded [&_code]:bg-amber-100 [&_code]:px-1 [&_code]:py-0.5 [&_code]:font-mono [&_code]:text-xs">
            @foreach ($this->setupSteps as $step)
                <li wire:key="step-{{ $type }}-{{ $loop->index }}">{!! $step !!}</li>
            @endforeach
        </ol>
        <p class="mt-3 text-xs text-amber-700/80">
            Start <b>Meta Business Verification + App Review</b> early — it gates production, not your code.
            Hosted plans connect in one click via Meta Embedded Signup (coming soon); this manual form is for self-hosted / dev.
        </p>
    </details>

    {{-- Connected channels (management) --}}
    <h2 class="mb-2 text-sm font-semibold text-gray-700">Connected channels</h2>
    <div class="space-y-2">
        @forelse ($this->channels as $channel)
            <div wire:key="ch-{{ $channel->id }}" class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-3">
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
        @empty
            <p class="rounded-lg border border-gray-200 bg-white p-6 text-center text-sm text-gray-400">No channels connected yet.</p>
        @endforelse
    </div>
</div>
