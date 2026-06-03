<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Channels\ChannelManager;
use App\Domain\Channels\Enums\ChannelType;
use App\Domain\Messaging\Jobs\IngestInboundMessage;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Single endpoint per channel type for Meta webhooks: GET answers the
 * subscription verification challenge; POST verifies the signature (middleware),
 * normalises the payload, enqueues ingestion, and returns 200 immediately.
 */
class MetaWebhookController extends Controller
{
    public function __construct(private readonly ChannelManager $channels) {}

    /** GET — Meta subscription verification handshake. */
    public function verify(Request $request, string $type): Response
    {
        abort_unless(ChannelType::tryFrom($type) !== null, 404);

        $matches = $request->query('hub_mode') === 'subscribe'
            && hash_equals(
                (string) config('services.meta.webhook_verify_token'),
                (string) $request->query('hub_verify_token'),
            );

        abort_unless($matches, 403);

        return response((string) $request->query('hub_challenge'));
    }

    /** POST — receive, normalise, enqueue, return 200 fast. */
    public function handle(Request $request, string $type): JsonResponse
    {
        $channelType = ChannelType::from($type);

        foreach ($this->channels->driver($channelType)->parseInbound($request->json()->all()) as $inbound) {
            IngestInboundMessage::dispatch($inbound);
        }

        return response()->json(['received' => true]);
    }
}
