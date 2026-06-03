<?php

namespace App\Models;

use App\Domain\Tenancy\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * STUB (E3 — Inbox/Messaging): a customer conversation on a channel, with its
 * 24-hour window (`window_expires_at`), status, assignment, and AI toggle. The
 * full migration, relations, and window-tracking logic land in E3.
 *
 * @property Carbon|null $window_expires_at
 */
class Conversation extends Model
{
    use BelongsToWorkspace;
}
