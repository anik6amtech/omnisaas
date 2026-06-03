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
    public function execute(
        ChannelType $type,
        string $externalId,
        string $accessToken,
        ?string $name = null,
        ?string $appId = null,
        ?string $appSecret = null,
        ?string $verifyToken = null,
    ): Channel {
        $attributes = ['name' => $name, 'access_token' => $accessToken, 'status' => 'active'];

        // Only overwrite per-channel app credentials when provided (a plain
        // re-connect shouldn't wipe a configured "bring your own app" secret).
        foreach (['app_id' => $appId, 'app_secret' => $appSecret, 'verify_token' => $verifyToken] as $key => $value) {
            if ($value !== null && $value !== '') {
                $attributes[$key] = $value;
            }
        }

        return Channel::query()->updateOrCreate(
            ['type' => $type, 'external_id' => $externalId],
            $attributes,
        );
    }
}
