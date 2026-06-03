<?php

namespace App\Domain\Channels;

use App\Domain\Channels\Contracts\ChannelDriver;
use App\Domain\Channels\Drivers\MessengerDriver;
use App\Domain\Channels\Drivers\WhatsAppCloudDriver;
use App\Domain\Channels\Enums\ChannelType;
use App\Models\Channel;
use RuntimeException;

/**
 * Resolves a channel-agnostic {@see ChannelDriver} by channel type. Drivers are
 * stateless, so this is safe to resolve as a singleton under Octane.
 */
class ChannelManager
{
    public function driver(ChannelType $type): ChannelDriver
    {
        return match ($type) {
            ChannelType::WhatsApp => new WhatsAppCloudDriver,
            ChannelType::Facebook => new MessengerDriver,
            ChannelType::Instagram => throw new RuntimeException('Instagram driver lands in E11.'),
        };
    }

    public function for(Channel $channel): ChannelDriver
    {
        return $this->driver($channel->type);
    }
}
