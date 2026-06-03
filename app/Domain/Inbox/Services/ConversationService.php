<?php

namespace App\Domain\Inbox\Services;

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Customer;
use Illuminate\Support\Carbon;

/**
 * Owns conversation state and the 24-hour customer-service window. All callers
 * run with the channel's workspace active, so queries are tenant-scoped.
 */
class ConversationService
{
    /** Meta's customer-service window length. */
    public const int WINDOW_HOURS = 24;

    /**
     * Find the customer behind a channel-specific sender id, merging on the
     * channel identity, or create a new unified profile.
     */
    public function resolveCustomer(Channel $channel, string $senderId): Customer
    {
        $type = $channel->type->value;

        $customer = Customer::query()
            ->where("channel_identities->{$type}", $senderId)
            ->first();

        if ($customer !== null) {
            return $customer;
        }

        return Customer::query()->create([
            'workspace_id' => $channel->workspace_id,
            'phone' => $channel->type->value === 'whatsapp' ? $senderId : null,
            'channel_identities' => [$type => $senderId],
        ]);
    }

    /**
     * Open or refresh the conversation for this channel + customer, sliding the
     * 24-hour window forward (every inbound message resets it).
     */
    public function openOrRefresh(Channel $channel, Customer $customer): Conversation
    {
        $conversation = Conversation::query()->firstOrNew([
            'channel_id' => $channel->getKey(),
            'customer_id' => $customer->getKey(),
        ]);

        if (! $conversation->exists) {
            $conversation->workspace_id = $channel->workspace_id;
        }

        $conversation->window_expires_at = Carbon::now()->addHours(self::WINDOW_HOURS);
        $conversation->last_message_at = Carbon::now();
        $conversation->save();

        return $conversation;
    }
}
