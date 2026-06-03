<?php

namespace App\Domain\Inbox\Enums;

enum ConversationStatus: string
{
    case AiHandling = 'ai_handling';
    case NeedsHuman = 'needs_human';
    case Resolved = 'resolved';

    public function color(): string
    {
        return match ($this) {
            self::AiHandling => 'success',
            self::NeedsHuman => 'warning',
            self::Resolved => 'gray',
        };
    }
}
