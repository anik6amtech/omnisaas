<?php

use App\Domain\Channels\Drivers\MessengerDriver;
use App\Domain\Channels\Drivers\WhatsAppCloudDriver;
use App\Domain\Messaging\Data\OutboundMessage;
use App\Models\Channel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

it('parses a WhatsApp payload into canonical messages', function () {
    $messages = (new WhatsAppCloudDriver)->parseInbound([
        'entry' => [[
            'changes' => [[
                'value' => [
                    'metadata' => ['phone_number_id' => 'PHONE1'],
                    'messages' => [
                        ['from' => 'A', 'id' => 'wamid.1', 'type' => 'text', 'text' => ['body' => 'hi']],
                        ['from' => 'B', 'id' => 'wamid.2', 'type' => 'image', 'image' => ['id' => 'media1']],
                    ],
                ],
            ]],
        ]],
    ]);

    expect($messages)->toHaveCount(2)
        ->and($messages[0]->text)->toBe('hi')
        ->and($messages[0]->recipientId)->toBe('PHONE1')
        ->and($messages[1]->attachments)->toBe([['id' => 'media1']]);
});

it('sends a WhatsApp text message via the Graph API', function () {
    Http::fake([
        '*/messages' => Http::response(['messages' => [['id' => 'wamid.OUT']]]),
    ]);

    $channel = Channel::factory()->whatsapp()->create(['external_id' => 'PHONE1']);

    $result = (new WhatsAppCloudDriver)->send($channel, new OutboundMessage(
        conversationId: '8801700000000',
        body: 'Hello!',
    ));

    expect($result->ok)->toBeTrue()->and($result->externalMessageId)->toBe('wamid.OUT');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'PHONE1/messages')
        && $request['text']['body'] === 'Hello!'
        && $request->hasHeader('Authorization'));
});

it('returns a failure result when the Graph API errors', function () {
    Http::fake([
        '*/messages' => Http::response(['error' => ['message' => 'Invalid token']], 401),
    ]);

    $channel = Channel::factory()->whatsapp()->create();

    $result = (new WhatsAppCloudDriver)->send($channel, new OutboundMessage('to', 'hi'));

    expect($result->ok)->toBeFalse()->and($result->error)->toBe('Invalid token');
});

it('sends a Messenger message via the Graph API', function () {
    Http::fake(['*/messages' => Http::response(['message_id' => 'm_OUT'])]);

    $channel = Channel::factory()->messenger()->create(['external_id' => 'PAGE1']);

    $result = (new MessengerDriver)->send($channel, new OutboundMessage('PSID1', 'Hi there'));

    expect($result->ok)->toBeTrue()->and($result->externalMessageId)->toBe('m_OUT');
});

it('encrypts the channel access token at rest', function () {
    $channel = Channel::factory()->create(['access_token' => 'super-secret-token']);

    expect($channel->fresh()->access_token)->toBe('super-secret-token');

    $raw = DB::table('channels')->where('id', $channel->id)->value('access_token');
    expect($raw)->not->toBe('super-secret-token')->not->toBeNull();
});
