<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Models\Subscription;

/**
 * Dunning + lifecycle transitions:
 * active → past_due (charge fails) → grace (retries exhausted) → suspended;
 * any → cancelled. Reminders/notifications hang off these transitions.
 */
class SubscriptionLifecycle
{
    public const int MAX_RETRIES = 3;

    public const int GRACE_DAYS = 3;

    public function recordFailedCharge(Subscription $subscription): void
    {
        $subscription->increment('failed_charges');

        if ($subscription->failed_charges >= self::MAX_RETRIES) {
            $subscription->update([
                'status' => SubscriptionStatus::Grace->value,
                'grace_ends_at' => now()->addDays(self::GRACE_DAYS),
            ]);
        } else {
            $subscription->update(['status' => SubscriptionStatus::PastDue->value]);
        }
    }

    public function endGraceIfExpired(Subscription $subscription): void
    {
        if ($subscription->status === SubscriptionStatus::Grace
            && $subscription->grace_ends_at?->isPast()) {
            $subscription->update(['status' => SubscriptionStatus::Suspended->value]);
        }
    }

    public function cancel(Subscription $subscription): void
    {
        $subscription->update([
            'status' => SubscriptionStatus::Cancelled->value,
            'cancelled_at' => now(),
        ]);
    }
}
