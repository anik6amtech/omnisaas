<?php

use App\Domain\AI\Jobs\GenerateAiReply;
use App\Domain\Channels\ChannelManager;
use App\Domain\Channels\Data\InboundMessage;
use App\Domain\Inbox\Enums\ConversationStatus;
use App\Domain\Inbox\Services\ConversationService;
use App\Domain\Messaging\Actions\SendMessageAction;
use App\Domain\Messaging\Enums\MessageDirection;
use App\Domain\Messaging\Events\MessageSent;
use App\Domain\Messaging\Jobs\IngestInboundMessage;
use App\Domain\Messaging\Jobs\SendOutboundMessage;
use App\Domain\Tenancy\Context\CurrentWorkspace;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function ingest(InboundMessage $m): void
{
    (new IngestInboundMessage($m))->handle(app(ConversationService::class), app(CurrentWorkspace::class));
}

function inbound(string $recipient, string $extId, string $from = '8801700000000', string $text = 'price koto?'): InboundMessage
{
    return new InboundMessage(
        channelType: 'whatsapp',
        recipientId: $recipient,
        externalMessageId: $extId,
        senderId: $from,
        text: $text,
    );
}

it('ingests an inbound message: customer, conversation, window, and message', function () {
    Queue::fake([GenerateAiReply::class]);
    $channel = Channel::factory()->whatsapp()->create(['external_id' => 'PHONE1']);

    ingest(inbound('PHONE1', 'wamid.1'));

    $customer = Customer::withoutGlobalScopes()->where('workspace_id', $channel->workspace_id)->sole();
    expect($customer->channel_identities['whatsapp'])->toBe('8801700000000');

    $conversation = Conversation::withoutGlobalScopes()->sole();
    expect($conversation->isWithinWindow())->toBeTrue()
        ->and($conversation->channel_id)->toBe($channel->id);

    $message = Message::withoutGlobalScopes()->sole();
    expect($message->body)->toBe('price koto?')
        ->and($message->direction)->toBe(MessageDirection::In)
        ->and($message->external_id)->toBe('wamid.1');

    Queue::assertPushed(GenerateAiReply::class);
});

it('is idempotent on the external message id', function () {
    Queue::fake([GenerateAiReply::class]);
    Channel::factory()->whatsapp()->create(['external_id' => 'PHONE1']);

    ingest(inbound('PHONE1', 'wamid.dup'));
    ingest(inbound('PHONE1', 'wamid.dup'));

    expect(Message::withoutGlobalScopes()->count())->toBe(1);
});

it('does not dispatch AI when the conversation has AI disabled', function () {
    Queue::fake([GenerateAiReply::class]);
    $channel = Channel::factory()->whatsapp()->create(['external_id' => 'PHONE1']);

    // First message opens the conversation; turn AI off, then send another.
    ingest(inbound('PHONE1', 'wamid.1'));
    Conversation::withoutGlobalScopes()->sole()->update(['ai_enabled' => false]);
    ingest(inbound('PHONE1', 'wamid.2'));

    Queue::assertPushed(GenerateAiReply::class, 1); // only the first
});

it('ignores inbound for an unknown channel', function () {
    Queue::fake();
    ingest(inbound('UNKNOWN', 'wamid.x'));

    expect(Message::withoutGlobalScopes()->count())->toBe(0);
    Queue::assertNothingPushed();
});

it('sends an outbound message in-window via the channel and broadcasts', function () {
    Event::fake([MessageSent::class]);
    Http::fake(['*/messages' => Http::response(['messages' => [['id' => 'wamid.OUT']]])]);

    $conversation = Conversation::factory()->create(); // WhatsApp, in-window
    $message = Message::factory()->outbound()->create([
        'workspace_id' => $conversation->workspace_id,
        'conversation_id' => $conversation->id,
        'status' => 'queued',
    ]);

    (new SendOutboundMessage($message))->handle(app(CurrentWorkspace::class), app(ChannelManager::class));

    expect($message->fresh()->status)->toBe('sent')
        ->and($message->fresh()->external_id)->toBe('wamid.OUT');
    Http::assertSent(fn ($r) => str_contains($r->url(), '/messages'));
    Event::assertDispatched(MessageSent::class);
});

it('refuses to send out-of-window and escalates AI replies to a human', function () {
    Http::fake();
    $conversation = Conversation::factory()->windowExpired()->create();
    $message = Message::factory()->outbound()->create([
        'workspace_id' => $conversation->workspace_id,
        'conversation_id' => $conversation->id,
        'status' => 'queued',
    ]);

    (new SendOutboundMessage($message))->handle(app(CurrentWorkspace::class), app(ChannelManager::class));

    expect($message->fresh()->status)->toBe('failed')
        ->and($message->fresh()->meta['error'])->toBe('window_closed')
        ->and($conversation->fresh()->status)->toBe(ConversationStatus::NeedsHuman);
    Http::assertNothingSent();
});

it('SendMessageAction persists a queued outbound message and dispatches the dispatcher', function () {
    Queue::fake([SendOutboundMessage::class]);
    $conversation = Conversation::factory()->create();

    app(CurrentWorkspace::class)->set($conversation->workspace_id);
    $message = (new SendMessageAction)->execute($conversation, 'On its way!');

    expect($message->status)->toBe('queued')->and($message->body)->toBe('On its way!');
    Queue::assertPushed(SendOutboundMessage::class);
});
