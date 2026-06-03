<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Contracts\PaymentGateway;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * Hosted-checkout link for a subscription invoice. The invoice is settled (and
 * the subscription activated) only by the IPN — never the browser return.
 */
class CreateSubscriptionPaymentLink
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function execute(Invoice $invoice): string
    {
        /** @var Payment $payment */
        $payment = $invoice->payments()->create([
            'workspace_id' => $invoice->workspace_id,
            'tran_id' => 'SUB-'.strtoupper(Str::random(12)),
            'amount' => $invoice->amount,
            'currency' => $invoice->currency,
            'status' => 'pending',
        ]);

        return $this->gateway->createSession($payment, [
            'product' => 'Subscription '.$invoice->number,
        ])['redirect_url'];
    }
}
