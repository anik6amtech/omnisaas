<?php

namespace App\Domain\Channels\Jobs;

use App\Domain\Channels\ChannelManager;
use App\Domain\Channels\Drivers\InstagramDriver;
use App\Domain\Tenancy\Context\CurrentWorkspace;
use App\Models\Channel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Public reply to a comment (the visible half of comment-to-DM) — queue:
 * dispatch. The private DM is the AI's normal reply on the opened conversation.
 */
class SendCommentReply implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $channelId,
        public string $commentId,
        public string $text,
    ) {
        $this->onQueue('dispatch');
    }

    public function handle(ChannelManager $channels, CurrentWorkspace $workspace): void
    {
        $channel = Channel::query()->withoutGlobalScopes()->find($this->channelId);

        if ($channel === null) {
            return;
        }

        $workspace->set($channel->workspace_id);

        try {
            $driver = $channels->for($channel);

            if ($driver instanceof InstagramDriver) {
                $driver->replyToComment($channel, $this->commentId, $this->text);
            }
        } finally {
            $workspace->forget();
        }
    }
}
