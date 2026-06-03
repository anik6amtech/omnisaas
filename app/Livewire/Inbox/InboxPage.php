<?php

namespace App\Livewire\Inbox;

use App\Domain\Inbox\Enums\ConversationStatus;
use App\Domain\Messaging\Actions\SendMessageAction;
use App\Domain\Messaging\Enums\MessageAuthor;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The bespoke tenant inbox: conversation list + live thread + composer, with the
 * AI⇄human toggle, takeover, and resolve. Queries are tenant-isolated by the
 * workspace global scope; Reverb pushes new messages in real time.
 */
#[Layout('components.layouts.app')]
class InboxPage extends Component
{
    use InteractsWithWorkspace;

    public ?string $selectedId = null;

    public string $filter = 'all'; // all | ai_handling | needs_human | resolved

    public string $body = '';

    /**
     * @return Collection<int, Conversation>
     */
    #[Computed]
    public function conversations(): Collection
    {
        return Conversation::query()
            ->with(['customer', 'channel'])
            ->when($this->filter !== 'all', fn ($q) => $q->where('status', $this->filter))
            ->orderByDesc('last_message_at')
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function conversation(): ?Conversation
    {
        return $this->selectedId !== null
            ? Conversation::query()->with(['customer', 'channel'])->find($this->selectedId)
            : null;
    }

    /**
     * @return Collection<int, Message>
     */
    #[Computed]
    public function messages(): Collection
    {
        return $this->selectedId !== null
            ? Message::query()->where('conversation_id', $this->selectedId)->orderBy('created_at')->get()
            : collect();
    }

    public function selectConversation(string $id): void
    {
        $this->selectedId = $id;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    public function sendReply(SendMessageAction $send): void
    {
        $conversation = $this->conversation();
        $body = trim($this->body);

        if ($conversation === null || $body === '') {
            return;
        }

        $send->execute($conversation, $body, MessageAuthor::Agent, auth()->id());

        $this->body = '';
        unset($this->messages);
    }

    public function toggleAi(): void
    {
        $conversation = $this->conversation();
        $conversation?->update(['ai_enabled' => ! $conversation->ai_enabled]);
        unset($this->conversation);
    }

    public function takeOver(): void
    {
        $this->conversation()?->update([
            'ai_enabled' => false,
            'status' => ConversationStatus::NeedsHuman,
            'assigned_to' => auth()->id(),
        ]);
        unset($this->conversation, $this->conversations);
    }

    public function resolve(): void
    {
        $this->conversation()?->update(['status' => ConversationStatus::Resolved]);
        unset($this->conversation, $this->conversations);
    }

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        $workspaceId = auth()->user()?->current_workspace_id;

        $listeners = [
            "echo-private:workspace.{$workspaceId}.inbox,.message.received" => 'refreshInbox',
            "echo-private:workspace.{$workspaceId}.inbox,.conversation.escalated" => 'refreshInbox',
        ];

        if ($this->selectedId !== null) {
            $listeners["echo-private:conversation.{$this->selectedId},.message.sent"] = 'refreshThread';
            $listeners["echo-private:conversation.{$this->selectedId},.message.received"] = 'refreshThread';
        }

        return $listeners;
    }

    public function refreshInbox(): void
    {
        unset($this->conversations);
    }

    public function refreshThread(): void
    {
        unset($this->messages, $this->conversations);
    }

    public function render()
    {
        return view('livewire.inbox.inbox-page');
    }
}
