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
 * Facebook Messenger (Messenger Platform / Graph API) driver.
 */
class MessengerDriver extends AbstractMetaDriver
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, InboundMessage>
     */
    public function parseInbound(array $payload): array
    {
        $messages = [];

        foreach (Arr::get($payload, 'entry', []) as $entry) {
            $recipientId = (string) Arr::get($entry, 'id'); // the Page id

            foreach (Arr::get($entry, 'messaging', []) as $event) {
                if (! Arr::has($event, 'message')) {
                    continue; // delivery/read receipts, postbacks handled separately
                }

                $messages[] = new InboundMessage(
                    channelType: ChannelType::Facebook->value,
                    recipientId: $recipientId,
                    externalMessageId: (string) Arr::get($event, 'message.mid'),
                    senderId: (string) Arr::get($event, 'sender.id'),
                    text: Arr::get($event, 'message.text'),
                    attachments: Arr::get($event, 'message.attachments', []),
                    raw: $event,
                );
            }
        }

        return $messages;
    }

    public function send(Channel $channel, OutboundMessage $message): SendResult
    {
        $response = $this->graph($channel)->post("{$channel->external_id}/messages", [
            'recipient' => ['id' => $message->recipientId],
            'messaging_type' => 'RESPONSE',
            'message' => ['text' => $message->body],
        ]);

        if ($response->failed()) {
            return SendResult::failed($response->json('error.message', 'Messenger send failed'));
        }

        return SendResult::ok((string) $response->json('message_id'));
    }

    public function sendTemplate(Channel $channel, TemplateMessage $template): SendResult
    {
        // Messenger uses message tags rather than pre-approved templates.
        $response = $this->graph($channel)->post("{$channel->external_id}/messages", [
            'recipient' => ['id' => Arr::get($template->variables, 'to')],
            'messaging_type' => 'MESSAGE_TAG',
            'tag' => Arr::get($template->variables, 'tag', 'POST_PURCHASE_UPDATE'),
            'message' => ['text' => Arr::get($template->variables, 'body', '')],
        ]);

        if ($response->failed()) {
            return SendResult::failed($response->json('error.message', 'Messenger tag send failed'));
        }

        return SendResult::ok((string) $response->json('message_id'));
    }
}
