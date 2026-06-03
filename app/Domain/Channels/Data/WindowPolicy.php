<?php

namespace App\Domain\Channels\Data;

use DateTimeImmutable;

/**
 * Whether a free reply is allowed for a conversation right now, and which tags
 * are legal. The outbound dispatcher consults this before every send to stay
 * inside Meta's 24-hour window rules. Stub — finalized in E3.
 */
final readonly class WindowPolicy
{
    /**
     * @param  array<int, string>  $allowedTags  Non-promo tags legal out-of-window.
     */
    public function __construct(
        public bool $freeReplyAllowed,
        public ?DateTimeImmutable $windowExpiresAt = null,
        public array $allowedTags = [],
    ) {}
}
