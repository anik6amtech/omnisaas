<?php

namespace App\Http\Middleware;

use App\Domain\Channels\ChannelManager;
use App\Domain\Channels\Enums\ChannelType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects any inbound Meta webhook whose X-Hub-Signature-256 doesn't match
 * (HMAC of the raw body with the app secret). Runs before the controller so a
 * forged payload never reaches the pipeline.
 */
class VerifyMetaWebhookSignature
{
    public function __construct(private readonly ChannelManager $channels) {}

    public function handle(Request $request, Closure $next): Response
    {
        $type = ChannelType::tryFrom((string) $request->route('type'));

        if ($type === null || ! $this->channels->driver($type)->verifyWebhook($request)) {
            abort(403, 'Invalid webhook signature.');
        }

        return $next($request);
    }
}
