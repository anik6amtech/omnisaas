<?php

namespace App\Domain\Knowledge\Services;

/**
 * Splits a document into overlapping character windows for embedding. Simple
 * and deterministic; smarter sentence-aware chunking can replace this later.
 */
class TextChunker
{
    public function __construct(
        private int $maxChars = 1000,
        private int $overlap = 100,
    ) {}

    /**
     * @return array<int, string>
     */
    public function chunk(string $text): array
    {
        $text = trim((string) preg_replace('/\s+/', ' ', $text));

        if ($text === '') {
            return [];
        }

        $length = mb_strlen($text);

        if ($length <= $this->maxChars) {
            return [$text];
        }

        $chunks = [];
        $step = max(1, $this->maxChars - $this->overlap);

        for ($start = 0; $start < $length; $start += $step) {
            $chunks[] = mb_substr($text, $start, $this->maxChars);
        }

        return $chunks;
    }
}
