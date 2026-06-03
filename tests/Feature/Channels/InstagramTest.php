<?php

use App\Domain\AI\Jobs\GenerateAiReply;
use App\Domain\Channels\ChannelManager;
use App\Domain\Channels\Data\InboundMessage;
use App\Domain\Channels\Drivers\InstagramDriver;
use App\Domain\Channels\Enums\ChannelType;
use App\Domain\Channels\Jobs\SendCommentReply;
use App\Domain\Inbox\Services\ConversationService;
use App\Domain\Messaging\Data\OutboundMessage;
use App\Domain\Messaging\Jobs\IngestInboundMessage;
use App\Domain\Tenancy\Context\CurrentWorkspace;
use App\Models\Channel;
use App\Models\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('resolves the Instagram driver from the channel manager', function () {
    expect(app(ChannelManager::class)->driver(ChannelType::Instagram))->toBeInstanceOf(InstagramDriver::class);
});

it('parses Instagram DMs and comments', function () {
    $messages = (new InstagramDriver)->parseInbound([
        'entry' => [[
            'id' => 'IG1',
            'messaging' => [[
                'sender' => ['id' => 'USER1'],
                'message' => ['mid' => 'ig_m1', 'text' => 'dam koto?'],
            ]],
            'changes' => [[
                'field' => 'comments',
                'value' => ['id' => 'COMMENT1', 'from' => ['id' => 'USER2'], 'text' => 'price?'],
            ]],
        ]],
    ]);

    expect($messages)->toHaveCount(2)
        ->and($messages[0]->isComment())->toBeFalse()
        ->and($messages[0]->text)->toBe('dam koto?')
        ->and($messages[1]->isComment())->toBeTrue()
        ->and($messages[1]->commentId)->toBe('COMMENT1')
        ->and($messages[1]->senderId)->toBe('USER2');
});

it('sends an Instagram DM and replies to a comment via the Graph API', function () {
    Http::fake([
        '*/messages' => Http::response(['message_id' => 'ig_out']),
        '*/replies' => Http::response(['id' => 'reply_1']),
    ]);
    $channel = Channel::factory()->instagram()->create(['external_id' => 'IG1']);
    $driver = new InstagramDriver;

    expect($driver->send($channel, new OutboundMessage('USER1', 'Hi'))->externalMessageId)->toBe('ig_out')
        ->and($driver->replyToComment($channel, 'COMMENT1', 'DM করেছি ✅')->externalMessageId)->toBe('reply_1');
});

it('publicly replies to a comment then hands the DM to the AI (comment-to-DM)', function () {
    Queue::fake([SendCommentReply::class, GenerateAiReply::class]);
    $channel = Channel::factory()->instagram()->create(['external_id' => 'IG1']);

    $comment = new InboundMessage(
        channelType: ChannelType::Instagram->value,
        recipientId: 'IG1',
        externalMessageId: 'COMMENT1',
        senderId: 'USER2',
        text: 'price?',
        kind: 'comment',
        commentId: 'COMMENT1',
    );

    (new IngestInboundMessage($comment))->handle(app(ConversationService::class), app(CurrentWorkspace::class));

    expect(Message::withoutGlobalScopes()->where('external_id', 'COMMENT1')->exists())->toBeTrue();
    Queue::assertPushed(SendCommentReply::class, fn (SendCommentReply $job) => $job->commentId === 'COMMENT1');
    Queue::assertPushed(GenerateAiReply::class); // the private DM
});
