<?php

namespace App\Models;

use App\Domain\Tenancy\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

/**
 * STUB (E2 — Channels): a connected Meta channel (WhatsApp / Instagram /
 * Facebook) belonging to a workspace. The migration, the `access_token`
 * encrypted-vault cast, refresh handling, relations, and the driver binding all
 * land in E2. No migration exists yet — this only types the ChannelDriver
 * contract for the foundation.
 */
class Channel extends Model
{
    use BelongsToWorkspace;
}
