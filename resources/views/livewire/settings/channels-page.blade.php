@php
    $labels = ['whatsapp' => 'WhatsApp', 'facebook' => 'Facebook Messenger', 'instagram' => 'Instagram'];
    $idLabel = $type === 'whatsapp' ? 'Phone number ID' : ($type === 'instagram' ? 'Instagram account ID' : 'Page ID');
    $subscribeFields = ['whatsapp' => 'messages', 'facebook' => 'messages, messaging_postbacks', 'instagram' => 'messages, comments'][$type];
    $typeChannels = $this->channels->filter(fn ($c) => $c->type->value === $type);
@endphp

<div class="mx-auto max-w-3xl p-6">
    <h1 class="mb-1 text-xl font-semibold text-gray-900">Channels</h1>
    <p class="mb-6 text-sm text-gray-500">Connect your WhatsApp, Instagram, or Facebook Messenger accounts.</p>

    {{-- Channel tabs (green dot = at least one connected) --}}
    <div class="mb-6 flex gap-1 rounded-xl border border-gray-200 bg-gray-50 p-1">
        @foreach ($labels as $value => $label)
            @php $isConnected = $this->channels->contains(fn ($c) => $c->type->value === $value); @endphp
            <button type="button" wire:click="$set('type', '{{ $value }}')"
                @class([
                    'flex-1 rounded-lg px-3 py-2 text-sm font-medium transition',
                    'bg-white text-amber-700 shadow-sm ring-1 ring-amber-200' => $type === $value,
                    'text-gray-500 hover:text-gray-700' => $type !== $value,
                ])>
                <span class="inline-flex items-center gap-1.5">
                    @if ($isConnected) <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span> @endif
                    {{ $label }}
                </span>
            </button>
        @endforeach
    </div>

    {{-- Step 1 — Connect the account (shows the connection if one already exists) --}}
    <section class="mb-5 rounded-xl border border-gray-200 bg-white p-5">
        <div class="mb-4 flex items-center gap-2">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-600 text-xs font-semibold text-white">1</span>
            <h2 class="text-sm font-semibold text-gray-900">
                {{ $typeChannels->isNotEmpty() ? "Your {$labels[$type]} connection" : "Connect your {$labels[$type]} account" }}
            </h2>
        </div>

        @if ($typeChannels->isNotEmpty())
            <div class="space-y-2">
                @foreach ($typeChannels as $channel)
                    <div wire:key="conn-{{ $channel->id }}" class="flex items-center justify-between rounded-lg border border-green-200 bg-green-50/50 p-3">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <svg class="h-5 w-5 shrink-0 text-green-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900">{{ $channel->name ?? $labels[$type] }}</p>
                                <p class="truncate text-xs text-gray-500">{{ $idLabel }}: {{ $channel->external_id }}</p>
                            </div>
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
                @endforeach
            </div>

            <details class="mt-3 rounded-lg border border-gray-200">
                <summary class="cursor-pointer px-3 py-2 text-sm font-medium text-gray-600">Connect another {{ $labels[$type] }} account / update token</summary>
                <div class="border-t border-gray-200 p-4">
                    @include('livewire.settings._connect-form')
                </div>
            </details>
        @else
            @include('livewire.settings._connect-form')
        @endif
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
    <details class="rounded-xl border border-amber-200 bg-amber-50/60 p-5">
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
</div>
