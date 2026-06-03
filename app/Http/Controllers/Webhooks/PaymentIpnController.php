<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Billing\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * SSLCommerz IPN — the server-to-server source of truth. Validated by val_id,
 * idempotent on tran_id. Browser return URLs are informational only.
 */
class PaymentIpnController extends Controller
{
    public function ipn(Request $request, PaymentGateway $gateway): Response
    {
        $payload = $request->all();

        $payment = Payment::query()
            ->withoutGlobalScopes()
            ->where('tran_id', $request->input('tran_id'))
            ->first();

        if ($payment === null) {
            return response('unknown transaction', 404);
        }

        // Idempotent: a duplicate IPN for an already-settled payment is a no-op.
        if (in_array($payment->status, ['paid', 'failed'], true)) {
            return response('already processed', 200);
        }

        if (! $gateway->validateIpn($payload)) {
            $payment->update(['status' => 'failed', 'raw' => $payload]);

            return response('invalid', 200);
        }

        DB::transaction(function () use ($payment, $payload, $request): void {
            $payment->update([
                'status' => 'paid',
                'val_id' => $request->input('val_id'),
                'paid_at' => now(),
                'raw' => $payload,
            ]);

            $payable = $payment->payable;

            if ($payable instanceof Order) {
                $payable->update(['payment_status' => 'paid', 'status' => 'confirmed']);
            }
            // Subscription activation is handled in E8.
        });

        return response('OK', 200);
    }

    /** Browser return — NOT trusted for settlement; just shows a status page. */
    public function return(Request $request): Response
    {
        return response('Payment '.$request->query('status', 'received').'. You may close this window.');
    }
}
