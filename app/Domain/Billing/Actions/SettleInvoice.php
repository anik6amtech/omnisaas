<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

/**
 * A paid invoice activates/extends the subscription and clears dunning state.
 */
class SettleInvoice
{
    public function execute(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            $invoice->update(['status' => 'paid', 'paid_at' => now()]);

            $subscription = $invoice->subscription;
            $periodEnd = $subscription->plan->billing_cycle === 'yearly'
                ? now()->addYear()
                : now()->addMonth();

            $subscription->update([
                'status' => SubscriptionStatus::Active->value,
                'current_period_ends_at' => $periodEnd,
                'grace_ends_at' => null,
                'failed_charges' => 0,
            ]);
        });
    }
}
