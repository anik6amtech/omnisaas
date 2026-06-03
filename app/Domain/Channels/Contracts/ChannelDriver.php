<?php

namespace App\Domain\Channels\Contracts;

use App\Domain\Channels\Data\InboundMessage;
use App\Domain\Channels\Data\WindowPolicy;
use App\Domain\Messaging\Data\OutboundMessage;
use App\Domain\Messaging\Data\SendResult;
use App\Domain\Messaging\Data\TemplateMessage;
use App\Models\Channel;
use App\Models\Conversation;
use Illuminate\Http\Request;

/**
 * The one contract every channel implementation satisfies, so the rest of the
 * system stays channel-agnostic. A `ChannelManager` (Laravel Manager pattern)
 * resolves a driver by channel type; implementations land in E2 as
 * WhatsAppCloudDriver, MessengerDriver, and (E11) InstagramDriver.
 *
 * Contract only — no implementations in this phase. See architecture doc §4.
 */
interface ChannelDriver
{
    /**
     * Verify the inbound webhook signature (Meta X-Hub-Signature-256, HMAC).
     * The app secret is passed in (per-channel, or the shared app's) so the
     * driver stays stateless.
     */
    public function verifyWebhook(Request $request, string $appSecret): bool;

    /**
     * Map a channel-specific webhook payload to canonical inbound messages.
     *
     * @param  array<string, mixed>  $payload
     * @return array<int, InboundMessage>
     */
    public function parseInbound(array $payload): array;

    /**
     * Send a free-form (in-window) message via the channel's send API.
     */
    public function send(Channel $channel, OutboundMessage $message): SendResult;

    /**
     * Send a pre-approved template message (for out-of-window re-engagement).
     */
    public function sendTemplate(Channel $channel, TemplateMessage $template): SendResult;

    /**
     * Resolve whether a free reply is currently allowed for this conversation
     * (24-hour window / tag legality) — the dispatcher checks this before every
     * send.
     */
    public function windowRules(Conversation $conversation): WindowPolicy;
}
