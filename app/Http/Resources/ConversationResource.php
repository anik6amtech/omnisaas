<?php

namespace App\Http\Resources;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'ai_enabled' => $this->ai_enabled,
            'within_window' => $this->isWithinWindow(),
            'window_expires_at' => $this->window_expires_at,
            'last_message_at' => $this->last_message_at,
            'customer' => [
                'id' => $this->customer?->id,
                'name' => $this->customer?->name,
            ],
            'channel' => [
                'type' => $this->channel?->type->value,
            ],
            'messages' => MessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
