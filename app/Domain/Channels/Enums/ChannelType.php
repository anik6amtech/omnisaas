<?php

namespace App\Domain\Channels\Enums;

/**
 * The Meta channels OmniReply integrates with. WhatsApp + Facebook Messenger
 * ship in E2/E3; Instagram lands in E11.
 */
enum ChannelType: string
{
    case WhatsApp = 'whatsapp';
    case Instagram = 'instagram';
    case Facebook = 'facebook';

    public function label(): string
    {
        return match ($this) {
            self::WhatsApp => 'WhatsApp',
            self::Instagram => 'Instagram',
            self::Facebook => 'Facebook Messenger',
        };
    }

    /** Channel brand colour (UI coding per the UX spec). */
    public function color(): string
    {
        return match ($this) {
            self::WhatsApp => 'green',
            self::Instagram => 'pink',
            self::Facebook => 'blue',
        };
    }
}
