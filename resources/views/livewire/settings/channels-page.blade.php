<div class="mx-auto max-w-3xl p-6">
    <h1 class="mb-1 text-xl font-semibold text-gray-900">Channels</h1>
    <p class="mb-6 text-sm text-gray-500">Connect your WhatsApp, Instagram, or Facebook Messenger accounts.</p>

    {{-- Connect form --}}
    <form wire:submit="connect" autocomplete="off" class="mb-8 space-y-4 rounded-xl border border-gray-200 bg-white p-5">
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
                <input wire:model="name" placeholder="e.g. Sadia's Store" autocomplete="off"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500" />
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                {{ $type === 'whatsapp' ? 'Phone number ID' : ($type === 'instagram' ? 'Instagram account ID' : 'Page ID') }}
            </label>
            <input wire:model="external_id" autocomplete="off"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500" />
            @error('external_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                <p class="text-xs text-gray-500">Connecting your <b>own</b> Meta app? Enter its credentials — they're stored encrypted, per channel. Leave blank to use the platform's shared app.</p>
                <input wire:model="app_id" placeholder="App ID" autocomplete="off"
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" />
                <input type="password" wire:model="app_secret" placeholder="App secret (HMAC)" autocomplete="new-password"
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" />
                <input wire:model="verify_token" placeholder="Webhook verify token" autocomplete="off"
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" />
            </div>
        </details>

        <div class="rounded-lg bg-gray-50 p-3 text-xs text-gray-500">
            <b>Hosted (shared app):</b> webhook URL <code class="font-mono text-gray-700">{{ $this->webhookUrl($type) }}</code>;
            verify token + app secret come from the platform (<code class="font-mono">.env</code>).<br>
            <b>Own app:</b> after connecting, use that channel's dedicated webhook URL shown below, with the verify token + app secret you entered above.
        </div>

        <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 font-medium text-white hover:bg-amber-700">Connect channel</button>
    </form>

    {{-- Per-channel setup guide (reactive to the selected channel) --}}
    <div class="mb-8 rounded-xl border border-amber-200 bg-amber-50/60 p-5">
        <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold text-amber-800">
            <span>Setup guide — {{ ['whatsapp' => 'WhatsApp', 'facebook' => 'Facebook Messenger', 'instagram' => 'Instagram'][$type] ?? '' }}</span>
        </h2>
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
    <div class="space-y-2">
        @forelse ($this->channels as $channel)
            <div wire:key="ch-{{ $channel->id }}" class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-3">
                <div class="min-w-0">
                    <p class="font-medium text-gray-900">{{ $channel->name ?? $channel->type->label() }}</p>
                    <p class="text-xs text-gray-500">{{ $channel->type->label() }} · {{ $channel->external_id }}</p>
                    @if ($channel->app_id)
                        <p class="mt-0.5 truncate text-xs text-gray-400">Webhook: <code class="font-mono">{{ $this->channelWebhookUrl($channel) }}</code></p>
                    @endif
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
