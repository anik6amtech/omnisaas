<?php

namespace App\Domain\Messaging\Data;

/**
 * A message the system intends to send to a customer. Stub — the outbound
 * dispatcher (E3) consumes this; fields finalized there.
 */
final readonly class OutboundMessage
{
    /**
     * @param  array<int, array<string, mixed>>  $attachments
     */
    public function __construct(
        public string $recipientId,   // the customer's channel id (wa_id / psid)
        public string $body,
        public string $author = 'ai', // ai | agent
        public array $attachments = [],
        public ?string $idempotencyKey = null,
    ) {}
}
