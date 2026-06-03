<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;

/**
 * Begins a subscription (state machine entry: trialing). Trial plans get access
 * immediately with no invoice; non-trial plans raise a first invoice to collect
 * before activation (IPN → SettleInvoice → active).
 */
class StartSubscription
{
    public function __construct(private readonly RaiseInvoice $raiseInvoice) {}

    public function execute(Plan $plan): Subscription
    {
        $trial = $plan->trial_days > 0;

        $subscription = Subscription::query()->create([
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Trialing->value,
            'trial_ends_at' => $trial ? now()->addDays($plan->trial_days) : null,
            'current_period_ends_at' => $trial ? now()->addDays($plan->trial_days) : null,
        ]);

        if (! $trial) {
            $this->raiseInvoice->execute($subscription);
        }

        return $subscription;
    }

    public function pendingInvoice(Subscription $subscription): ?Invoice
    {
        return $subscription->invoices()->where('status', 'pending')->latest()->first();
    }
}
