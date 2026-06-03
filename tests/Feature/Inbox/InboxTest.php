<?php

use App\Domain\Inbox\Enums\ConversationStatus;
use App\Domain\Messaging\Enums\MessageAuthor;
use App\Domain\Messaging\Jobs\SendOutboundMessage;
use App\Domain\Tenancy\Context\CurrentWorkspace;
use App\Livewire\Inbox\InboxPage;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

/**
 * @return array{Workspace, User}
 */
function actingSeller(): array
{
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create(['current_workspace_id' => $workspace->id]);
    $workspace->users()->attach($user, ['role' => 'owner']);

    test()->actingAs($user, 'web');
    app(CurrentWorkspace::class)->set($workspace);

    return [$workspace, $user];
}

it('lists conversations for the active workspace and isolates other tenants', function () {
    [$workspace] = actingSeller();
    $mine = Conversation::factory()->recycle($workspace)->create();
    $theirs = Conversation::factory()->create(); // different workspace

    $component = Livewire::test(InboxPage::class);

    $ids = $component->instance()->conversations->pluck('id');
    expect($ids)->toContain($mine->id)->not->toContain($theirs->id);
});

it('sends an agent reply through SendMessageAction', function () {
    Queue::fake([SendOutboundMessage::class]);
    [$workspace, $user] = actingSeller();
    $conversation = Conversation::factory()->recycle($workspace)->create();

    Livewire::test(InboxPage::class)
        ->set('selectedId', $conversation->id)
        ->set('body', 'On its way!')
        ->call('sendReply')
        ->assertSet('body', '');

    $reply = Message::withoutGlobalScopes()
        ->where('conversation_id', $conversation->id)
        ->where('author', MessageAuthor::Agent->value)
        ->sole();

    expect($reply->body)->toBe('On its way!')
        ->and($reply->sent_by)->toBe($user->id);
    Queue::assertPushed(SendOutboundMessage::class);
});

it('toggles AI, takes over, and resolves a conversation', function () {
    [$workspace, $user] = actingSeller();
    $conversation = Conversation::factory()->recycle($workspace)->create(['ai_enabled' => true]);

    $component = Livewire::test(InboxPage::class)->set('selectedId', $conversation->id);

    $component->call('toggleAi');
    expect($conversation->fresh()->ai_enabled)->toBeFalse();

    $component->call('takeOver');
    expect($conversation->fresh()->status)->toBe(ConversationStatus::NeedsHuman)
        ->and($conversation->fresh()->assigned_to)->toBe($user->id);

    $component->call('resolve');
    expect($conversation->fresh()->status)->toBe(ConversationStatus::Resolved);
});

it('requires authentication to reach the inbox', function () {
    $this->get('/app/inbox')->assertRedirect('/login');
});
