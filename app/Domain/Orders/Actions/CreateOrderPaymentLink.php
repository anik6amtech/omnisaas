<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Billing\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * Creates a pending Payment for an order and returns the hosted-checkout URL.
 * The order is only marked paid by the IPN (see PaymentIpnController).
 */
class CreateOrderPaymentLink
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function execute(Order $order): string
    {
        /** @var Payment $payment */
        $payment = $order->payments()->create([
            'workspace_id' => $order->workspace_id,
            'tran_id' => 'TXN-'.strtoupper(Str::random(12)),
            'amount' => $order->total,
            'currency' => $order->currency,
            'status' => 'pending',
        ]);

        $session = $this->gateway->createSession($payment, [
            'name' => $order->customer_name,
            'phone' => $order->customer_phone,
            'product' => 'Order '.$order->reference,
        ]);

        return $session['redirect_url'];
    }
}
