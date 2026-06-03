<?php

namespace App\Domain\Channels\Actions;

use App\Domain\Channels\Enums\ChannelType;
use App\Models\Channel;

/**
 * Connects (or re-connects) a Meta channel for the active workspace. Idempotent
 * on (type, external_id) within the workspace; the access token is encrypted at
 * rest by the model cast. Run with the workspace context set.
 *
 * Today this is the manual-token path used by the Settings → Channels page; the
 * Meta Embedded Signup / Facebook Login for Business OAuth flow resolves to the
 * same (external_id, access_token) and calls straight into here.
 */
class ConnectChannel
{
    public function execute(ChannelType $type, string $externalId, string $accessToken, ?string $name = null): Channel
    {
        return Channel::query()->updateOrCreate(
            ['type' => $type, 'external_id' => $externalId],
            ['name' => $name, 'access_token' => $accessToken, 'status' => 'active'],
        );
    }
}
