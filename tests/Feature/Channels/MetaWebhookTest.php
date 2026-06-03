<?php

use App\Domain\Messaging\Jobs\IngestInboundMessage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    config([
        'services.meta.app_secret' => 'app-secret',
        'services.meta.webhook_verify_token' => 'verify-token',
    ]);
});

function signedPost(string $type, array $payload): TestResponse
{
    $body = json_encode($payload);
    $signature = 'sha256='.hash_hmac('sha256', $body, 'app-secret');

    return test()->call(
        'POST',
        "/webhooks/meta/{$type}",
        [], [], [],
        ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => $signature],
        $body,
    );
}

it('answers the subscription verification challenge', function () {
    $this->get('/webhooks/meta/whatsapp?hub_mode=subscribe&hub_verify_token=verify-token&hub_challenge=42')
        ->assertOk()
        ->assertSee('42');
});

it('rejects a verification with the wrong token', function () {
    $this->get('/webhooks/meta/whatsapp?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=42')
        ->assertForbidden();
});

it('rejects an unsigned inbound webhook', function () {
    Queue::fake();

    $this->postJson('/webhooks/meta/whatsapp', ['entry' => []])
        ->assertForbidden();

    Queue::assertNothingPushed();
});

it('rejects a tampered signature', function () {
    Queue::fake();

    test()->call(
        'POST', '/webhooks/meta/whatsapp', [], [], [],
        ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => 'sha256=deadbeef'],
        json_encode(['entry' => []]),
    )->assertForbidden();

    Queue::assertNothingPushed();
});

it('ingests a signed WhatsApp webhook and enqueues the message', function () {
    Queue::fake();

    $payload = [
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'id' => 'WABA1',
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'messaging_product' => 'whatsapp',
                    'metadata' => ['phone_number_id' => 'PHONE1'],
                    'messages' => [[
                        'from' => '8801700000000',
                        'id' => 'wamid.ABC123',
                        'type' => 'text',
                        'text' => ['body' => 'price koto?'],
                    ]],
                ],
            ]],
        ]],
    ];

    signedPost('whatsapp', $payload)->assertOk()->assertJson(['received' => true]);

    Queue::assertPushed(IngestInboundMessage::class, function (IngestInboundMessage $job) {
        return $job->message->recipientId === 'PHONE1'
            && $job->message->externalMessageId === 'wamid.ABC123'
            && $job->message->senderId === '8801700000000'
            && $job->message->text === 'price koto?';
    });
});

it('ingests a signed Messenger webhook and enqueues the message', function () {
    Queue::fake();

    $payload = [
        'object' => 'page',
        'entry' => [[
            'id' => 'PAGE1',
            'messaging' => [[
                'sender' => ['id' => 'PSID1'],
                'recipient' => ['id' => 'PAGE1'],
                'message' => ['mid' => 'm_ABC', 'text' => 'ki ki ache?'],
            ]],
        ]],
    ];

    signedPost('facebook', $payload)->assertOk();

    Queue::assertPushed(IngestInboundMessage::class, fn (IngestInboundMessage $job) => $job->message->recipientId === 'PAGE1'
        && $job->message->text === 'ki ki ache?'
        && $job->message->channelType === 'facebook');
});
