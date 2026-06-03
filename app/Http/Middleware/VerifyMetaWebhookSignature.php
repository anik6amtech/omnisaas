<?php

namespace App\Http\Middleware;

use App\Domain\Channels\ChannelManager;
use App\Domain\Channels\Enums\ChannelType;
use App\Models\Channel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects any inbound Meta webhook whose X-Hub-Signature-256 doesn't match
 * (HMAC of the raw body with the app secret). Uses the per-channel app secret
 * when the route carries a {channel}; otherwise the shared app's (hosted model).
 * Runs before the controller so a forged payload never reaches the pipeline.
 */
class VerifyMetaWebhookSignature
{
    public function __construct(private readonly ChannelManager $channels) {}

    public function handle(Request $request, Closure $next): Response
    {
        $type = ChannelType::tryFrom((string) $request->route('type'));

        if ($type === null) {
            abort(404);
        }

        $secret = (string) config('services.meta.app_secret');

        if (($channelId = $request->route('channel')) !== null) {
            $channel = Channel::query()->withoutGlobalScopes()->find($channelId);

            if ($channel === null) {
                abort(404);
            }

            $secret = $channel->effectiveAppSecret();
        }

        if (! $this->channels->driver($type)->verifyWebhook($request, $secret)) {
            abort(403, 'Invalid webhook signature.');
        }

        return $next($request);
    }
}
