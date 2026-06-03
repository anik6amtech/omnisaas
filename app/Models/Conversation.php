<?php

namespace App\Models;

use App\Domain\Tenancy\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

/**
 * STUB (E3 — Inbox/Messaging): a customer conversation on a channel, with its
 * 24-hour window (`window_expires_at`), status, assignment, and AI toggle. The
 * migration, relations, and window-tracking logic land in E3. No migration
 * exists yet — this only types the ChannelDriver contract for the foundation.
 */
class Conversation extends Model
{
    use BelongsToWorkspace;
}
