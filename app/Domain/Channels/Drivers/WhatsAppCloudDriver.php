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
 * WhatsApp Business Platform (Cloud API) driver.
 */
class WhatsAppCloudDriver extends AbstractMetaDriver
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, InboundMessage>
     */
    public function parseInbound(array $payload): array
    {
        $messages = [];

        foreach (Arr::get($payload, 'entry', []) as $entry) {
            foreach (Arr::get($entry, 'changes', []) as $change) {
                $value = Arr::get($change, 'value', []);
                $recipientId = (string) Arr::get($value, 'metadata.phone_number_id');

                foreach (Arr::get($value, 'messages', []) as $message) {
                    $messages[] = new InboundMessage(
                        channelType: ChannelType::WhatsApp->value,
                        recipientId: $recipientId,
                        externalMessageId: (string) Arr::get($message, 'id'),
                        senderId: (string) Arr::get($message, 'from'),
                        text: Arr::get($message, 'text.body'),
                        attachments: $this->extractAttachments($message),
                        raw: $message,
                    );
                }
            }
        }

        return $messages;
    }

    public function send(Channel $channel, OutboundMessage $message): SendResult
    {
        $response = $this->graph($channel)->post("{$channel->external_id}/messages", [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->recipientFor($message),
            'type' => 'text',
            'text' => ['body' => $message->body],
        ]);

        if ($response->failed()) {
            return SendResult::failed($response->json('error.message', 'WhatsApp send failed'));
        }

        return SendResult::ok((string) $response->json('messages.0.id'));
    }

    public function sendTemplate(Channel $channel, TemplateMessage $template): SendResult
    {
        $response = $this->graph($channel)->post("{$channel->external_id}/messages", [
            'messaging_product' => 'whatsapp',
            'to' => Arr::get($template->variables, 'to'),
            'type' => 'template',
            'template' => [
                'name' => $template->templateName,
                'language' => ['code' => Arr::get($template->variables, 'language', 'en')],
            ],
        ]);

        if ($response->failed()) {
            return SendResult::failed($response->json('error.message', 'WhatsApp template send failed'));
        }

        return SendResult::ok((string) $response->json('messages.0.id'));
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array<int, array<string, mixed>>
     */
    protected function extractAttachments(array $message): array
    {
        $type = Arr::get($message, 'type');

        if (in_array($type, ['image', 'audio', 'video', 'document', 'sticker'], true)) {
            return [Arr::get($message, $type, [])];
        }

        return [];
    }

    protected function recipientFor(OutboundMessage $message): ?string
    {
        return $message->conversationId; // resolved to the customer's wa_id by the dispatcher in E3
    }
}
