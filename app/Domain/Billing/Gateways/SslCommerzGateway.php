<?php

namespace App\Domain\Billing\Gateways;

use App\Domain\Billing\Contracts\PaymentGateway;
use App\Models\Payment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SslCommerzGateway implements PaymentGateway
{
    /**
     * @param  array<string, mixed>  $options
     * @return array{tran_id: string, redirect_url: string}
     */
    public function createSession(Payment $payment, array $options = []): array
    {
        $response = Http::asForm()
            ->timeout(20)
            ->post($this->baseUrl().'/gwprocess/v4/api.php', [
                'store_id' => config('sslcommerz.store_id'),
                'store_passwd' => config('sslcommerz.store_password'),
                'total_amount' => $payment->amount,
                'currency' => $payment->currency,
                'tran_id' => $payment->tran_id,
                'success_url' => route('payments.return', ['status' => 'success']),
                'fail_url' => route('payments.return', ['status' => 'fail']),
                'cancel_url' => route('payments.return', ['status' => 'cancel']),
                'ipn_url' => route('payments.ipn'),
                'cus_name' => Arr::get($options, 'name', 'Customer'),
                'cus_email' => Arr::get($options, 'email', 'buyer@example.com'),
                'cus_phone' => Arr::get($options, 'phone', '01700000000'),
                'product_name' => Arr::get($options, 'product', 'Order'),
                'product_category' => 'general',
                'product_profile' => 'general',
                'shipping_method' => 'NO',
            ]);

        $data = $response->json();

        if (Arr::get($data, 'status') !== 'SUCCESS') {
            throw new RuntimeException('SSLCommerz session failed: '.Arr::get($data, 'failedreason', 'unknown'));
        }

        return [
            'tran_id' => $payment->tran_id,
            'redirect_url' => (string) Arr::get($data, 'GatewayPageURL'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function validateIpn(array $payload): bool
    {
        $valId = Arr::get($payload, 'val_id');

        if (empty($valId)) {
            return false;
        }

        $response = Http::timeout(20)->get($this->baseUrl().'/validator/api/validationserverAPI.php', [
            'val_id' => $valId,
            'store_id' => config('sslcommerz.store_id'),
            'store_passwd' => config('sslcommerz.store_password'),
            'format' => 'json',
        ]);

        return in_array(Arr::get($response->json(), 'status'), ['VALID', 'VALIDATED'], true);
    }

    private function baseUrl(): string
    {
        return rtrim((string) config(config('sslcommerz.sandbox') ? 'sslcommerz.sandbox_url' : 'sslcommerz.live_url'), '/');
    }
}
