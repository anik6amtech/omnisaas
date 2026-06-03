<?php

namespace App\Domain\Messaging\Data;

/**
 * Outcome of a channel send attempt. Stub — finalized in E3 (retries,
 * rate-limit backoff, and idempotency keys hang off this result).
 */
final readonly class SendResult
{
    public function __construct(
        public bool $ok,
        public ?string $externalMessageId = null,
        public ?string $error = null,
    ) {}

    public static function ok(string $externalMessageId): self
    {
        return new self(true, $externalMessageId);
    }

    public static function failed(string $error): self
    {
        return new self(false, null, $error);
    }
}
