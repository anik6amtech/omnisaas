<?php

namespace App\Domain\Channels\Drivers;

use App\Domain\Channels\Contracts\ChannelDriver;
use App\Domain\Channels\Data\WindowPolicy;
use App\Models\Channel;
use App\Models\Conversation;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Shared Meta-platform behaviour: webhook HMAC verification, the 24-hour window
 * rule, and a configured Graph API HTTP client. Concrete drivers implement the
 * channel-specific payload parsing and send calls.
 */
abstract class AbstractMetaDriver implements ChannelDriver
{
    /**
     * Verify Meta's X-Hub-Signature-256 (HMAC-SHA256 of the raw body with the
     * app secret). Constant-time comparison; rejects when unconfigured.
     */
    public function verifyWebhook(Request $request, string $appSecret): bool
    {
        $signature = (string) $request->header('X-Hub-Signature-256', '');

        if ($appSecret === '' || ! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expected, $signature);
    }

    /**
     * The 24-hour customer-service window. Free replies are allowed while it is
     * open; once closed only non-promotional tags are legal. The AI may never
     * ride the Human Agent tag (enforced by the dispatcher, not here).
     */
    public function windowRules(Conversation $conversation): WindowPolicy
    {
        $expiresAt = $conversation->window_expires_at;
        $open = $expiresAt !== null && $expiresAt->isFuture();

        return new WindowPolicy(
            freeReplyAllowed: $open,
            windowExpiresAt: $expiresAt?->toDateTimeImmutable(),
            allowedTags: $open ? [] : [
                'account_update',
                'confirmed_event_update',
                'post_purchase_update',
            ],
        );
    }

    /** A Graph API client authenticated with the channel's vaulted token. */
    protected function graph(Channel $channel): PendingRequest
    {
        return Http::baseUrl($this->graphBaseUrl())
            ->withToken((string) $channel->access_token)
            ->acceptJson()
            ->timeout(15)
            ->connectTimeout(5)
            ->retry(2, 200, throw: false);
    }

    protected function graphBaseUrl(): string
    {
        return rtrim((string) config('services.meta.graph_base'), '/')
            .'/'.config('services.meta.graph_version');
    }
}
