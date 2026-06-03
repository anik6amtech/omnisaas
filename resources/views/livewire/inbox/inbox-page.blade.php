<div class="flex h-full">
    {{-- Conversation list --}}
    <aside class="flex w-80 shrink-0 flex-col border-r border-gray-200 bg-white">
        <div class="flex gap-1 border-b border-gray-200 p-2 text-xs">
            @foreach (['all' => 'All', 'needs_human' => 'Needs human', 'ai_handling' => 'AI', 'resolved' => 'Resolved'] as $key => $label)
                <button wire:click="setFilter('{{ $key }}')"
                    @class([
                        'rounded-full px-3 py-1 font-medium',
                        'bg-amber-100 text-amber-800' => $filter === $key,
                        'text-gray-500 hover:bg-gray-100' => $filter !== $key,
                    ])>{{ $label }}</button>
            @endforeach
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto">
            @forelse ($this->conversations as $conversation)
                <button wire:key="conv-{{ $conversation->id }}" wire:click="selectConversation('{{ $conversation->id }}')"
                    @class([
                        'flex w-full items-start gap-3 border-b border-gray-100 p-3 text-left hover:bg-gray-50',
                        'bg-amber-50' => $selectedId === $conversation->id,
                    ])>
                    <span @class([
                        'mt-1 h-2.5 w-2.5 shrink-0 rounded-full',
                        'bg-green-500' => $conversation->status === \App\Domain\Inbox\Enums\ConversationStatus::AiHandling,
                        'bg-amber-500' => $conversation->status === \App\Domain\Inbox\Enums\ConversationStatus::NeedsHuman,
                        'bg-gray-300' => $conversation->status === \App\Domain\Inbox\Enums\ConversationStatus::Resolved,
                    ])></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <span class="truncate text-sm font-medium text-gray-900">{{ $conversation->customer->name ?? 'Customer' }}</span>
                            <span class="text-xs capitalize text-gray-500">{{ $conversation->channel->type->label() }}</span>
                        </div>
                        <p class="truncate text-xs text-gray-500">{{ $conversation->last_message_at?->diffForHumans() }}</p>
                    </div>
                </button>
            @empty
                <p class="p-6 text-center text-sm text-gray-400">No conversations yet.</p>
            @endforelse
        </div>
    </aside>

    {{-- Thread + composer --}}
    <section class="flex min-w-0 flex-1 flex-col bg-gray-50">
        @if ($this->conversation)
            <header class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3">
                <div>
                    <p class="font-medium text-gray-900">{{ $this->conversation->customer->name ?? 'Customer' }}</p>
                    <p class="text-xs text-gray-500">
                        @if ($this->conversation->isWithinWindow())
                            <span class="text-green-600">● Window open · {{ $this->conversation->window_expires_at->diffForHumans(null, true) }} left</span>
                        @else
                            <span class="text-red-600">● Window expired — free reply disabled</span>
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <button wire:click="toggleAi"
                        @class([
                            'rounded-lg px-3 py-1.5 font-medium',
                            'bg-green-100 text-green-700' => $this->conversation->ai_enabled,
                            'bg-gray-100 text-gray-600' => ! $this->conversation->ai_enabled,
                        ])>AI {{ $this->conversation->ai_enabled ? 'on' : 'off' }}</button>
                    <button wire:click="takeOver" class="rounded-lg bg-amber-100 px-3 py-1.5 font-medium text-amber-700 hover:bg-amber-200">Take over</button>
                    <button wire:click="resolve" class="rounded-lg bg-gray-100 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-200">Resolve</button>
                </div>
            </header>

            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto p-4">
                @foreach ($this->messages as $message)
                    <div wire:key="msg-{{ $message->id }}" @class(['flex', 'justify-end' => $message->direction === \App\Domain\Messaging\Enums\MessageDirection::Out])>
                        <div @class([
                            'max-w-md rounded-2xl px-4 py-2 text-sm',
                            'bg-white text-gray-900 shadow-sm' => $message->direction === \App\Domain\Messaging\Enums\MessageDirection::In,
                            'bg-amber-600 text-white' => $message->direction === \App\Domain\Messaging\Enums\MessageDirection::Out,
                        ])>
                            @if ($message->author === \App\Domain\Messaging\Enums\MessageAuthor::Ai)
                                <span class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide opacity-70">AI</span>
                            @elseif ($message->author === \App\Domain\Messaging\Enums\MessageAuthor::Agent)
                                <span class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide opacity-70">Agent</span>
                            @endif
                            {{ $message->body }}
                        </div>
                    </div>
                @endforeach
            </div>

            <form wire:submit="sendReply" class="flex items-center gap-2 border-t border-gray-200 bg-white p-3">
                <input wire:model="body" placeholder="Type a reply…"
                    class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500" />
                <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 font-medium text-white hover:bg-amber-700">Send</button>
            </form>
        @else
            <div class="flex flex-1 items-center justify-center text-sm text-gray-400">Select a conversation</div>
        @endif
    </section>
</div>
