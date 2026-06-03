<?php

namespace App\Domain\Billing\Actions;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Support\Str;

class RaiseInvoice
{
    public function execute(Subscription $subscription): Invoice
    {
        return $subscription->invoices()->create([
            'workspace_id' => $subscription->workspace_id,
            'number' => 'INV-'.strtoupper(Str::random(10)),
            'amount' => $subscription->plan->price_bdt,
            'currency' => 'BDT',
            'status' => 'pending',
            'due_at' => now(),
        ]);
    }
}
