<?php

namespace App\Domain\AI\Data;

/**
 * Classified intent for an inbound message. `language` is bn | bn_rom (Banglish)
 * | en — Banglish is first-class in this market.
 */
final readonly class IntentResult
{
    public function __construct(
        public string $intent,
        public float $confidence,
        public string $language = 'en',
    ) {}

    /** Intents that always route to a human regardless of confidence. */
    public function mustEscalate(): bool
    {
        return in_array($this->intent, config('ai.escalation_intents', []), true);
    }
}
