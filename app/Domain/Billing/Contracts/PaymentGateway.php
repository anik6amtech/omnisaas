<?php

namespace App\Domain\Billing\Contracts;

use App\Models\Payment;

/**
 * The payment executor contract. Implemented by SSLCommerz (E7/E8); the gateway
 * only initiates sessions and validates IPNs — all subscription/order state
 * lives in our own engine.
 */
interface PaymentGateway
{
    /**
     * Initiate a hosted-checkout session.
     *
     * @param  array<string, mixed>  $options  cus_name/phone/email, product_name, callback overrides
     * @return array{tran_id: string, redirect_url: string}
     */
    public function createSession(Payment $payment, array $options = []): array;

    /**
     * Validate an IPN payload server-to-server (by val_id). Returns true only
     * when the gateway confirms the payment is genuinely complete.
     *
     * @param  array<string, mixed>  $payload
     */
    public function validateIpn(array $payload): bool;
}
