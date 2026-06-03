<?php

namespace App\Domain\Channels\Drivers;

use App\Domain\Channels\Data\InboundMessage;
use App\Domain\Channels\Enums\ChannelType;
use App\Domain\Messaging\Data\OutboundMessage;
use App\Domain\Messaging\Data\SendResult;
use App\Domain\Messaging\Data\TemplateMessage;
use App\Models\Channel;
use Illuminate\Support\Arr;

/**
 * Instagram messaging (via the Messenger Platform / Graph API). Handles DMs and
 * post/reel comments (comment-to-DM). Out-of-window re-engagement uses One-Time
 * Notifications, never the Human Agent tag (Meta-prohibited for automation).
 */
class InstagramDriver extends AbstractMetaDriver
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, InboundMessage>
     */
    public function parseInbound(array $payload): array
    {
        $messages = [];

        foreach (Arr::get($payload, 'entry', []) as $entry) {
            $recipientId = (string) Arr::get($entry, 'id');

            // Direct messages.
            foreach (Arr::get($entry, 'messaging', []) as $event) {
                if (! Arr::has($event, 'message')) {
                    continue;
                }

                $messages[] = new InboundMessage(
                    channelType: ChannelType::Instagram->value,
                    recipientId: $recipientId,
                    externalMessageId: (string) Arr::get($event, 'message.mid'),
                    senderId: (string) Arr::get($event, 'sender.id'),
                    text: Arr::get($event, 'message.text'),
                    raw: $event,
                );
            }

            // Comments on posts/reels (comment-to-DM trigger).
            foreach (Arr::get($entry, 'changes', []) as $change) {
                if (Arr::get($change, 'field') !== 'comments') {
                    continue;
                }

                $value = Arr::get($change, 'value', []);

                $messages[] = new InboundMessage(
                    channelType: ChannelType::Instagram->value,
                    recipientId: $recipientId,
                    externalMessageId: (string) Arr::get($value, 'id'),
                    senderId: (string) Arr::get($value, 'from.id'),
                    text: Arr::get($value, 'text'),
                    raw: $value,
                    kind: 'comment',
                    commentId: (string) Arr::get($value, 'id'),
                );
            }
        }

        return $messages;
    }

    public function send(Channel $channel, OutboundMessage $message): SendResult
    {
        $response = $this->graph($channel)->post("{$channel->external_id}/messages", [
            'recipient' => ['id' => $message->recipientId],
            'message' => ['text' => $message->body],
        ]);

        if ($response->failed()) {
            return SendResult::failed($response->json('error.message', 'Instagram send failed'));
        }

        return SendResult::ok((string) $response->json('message_id'));
    }

    /** One-Time Notification (opt-in single follow-up after the window). */
    public function sendTemplate(Channel $channel, TemplateMessage $template): SendResult
    {
        $response = $this->graph($channel)->post("{$channel->external_id}/messages", [
            'recipient' => ['one_time_notif_token' => Arr::get($template->variables, 'token')],
            'message' => ['text' => Arr::get($template->variables, 'body', '')],
        ]);

        if ($response->failed()) {
            return SendResult::failed($response->json('error.message', 'Instagram OTN failed'));
        }

        return SendResult::ok((string) $response->json('message_id'));
    }

    /** Public reply to a comment (the "comment-to-DM" public acknowledgement). */
    public function replyToComment(Channel $channel, string $commentId, string $text): SendResult
    {
        $response = $this->graph($channel)->post("{$commentId}/replies", [
            'message' => $text,
        ]);

        if ($response->failed()) {
            return SendResult::failed($response->json('error.message', 'Instagram comment reply failed'));
        }

        return SendResult::ok((string) $response->json('id'));
    }
}
