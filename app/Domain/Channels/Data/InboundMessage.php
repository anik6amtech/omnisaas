<?php

namespace App\Domain\Channels\Data;

/**
 * Canonical, channel-agnostic inbound message produced by a ChannelDriver's
 * parseInbound(). Stub — fields are finalized alongside the pipeline in E2/E3.
 */
final readonly class InboundMessage
{
    /**
     * @param  array<int, array<string, mixed>>  $attachments
     * @param  array<string, mixed>  $raw  Original provider payload snapshot.
     */
    public function __construct(
        public string $channelType,        // whatsapp | instagram | facebook
        public string $recipientId,        // receiving channel's external_id (phone_number_id / page id)
        public string $externalMessageId,  // Meta message id — idempotency key
        public string $senderId,
        public ?string $text = null,
        public array $attachments = [],
        public array $raw = [],
    ) {}
}
