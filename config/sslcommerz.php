<?php

return [

    /*
    | SSLCommerz — the payment EXECUTOR (no native subscriptions). The IPN
    | (server-to-server, validated by val_id) is the source of truth; browser
    | return URLs are never trusted alone. Same gateway, two integrations:
    | buyer order payments (E7) and tenant subscription billing (E8).
    */

    'sandbox' => (bool) env('SSLCOMMERZ_SANDBOX', true),
    'store_id' => env('SSLCOMMERZ_STORE_ID'),
    'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
    'sandbox_url' => env('SSLCOMMERZ_SANDBOX_URL', 'https://sandbox.sslcommerz.com'),
    'live_url' => env('SSLCOMMERZ_LIVE_URL', 'https://securepay.sslcommerz.com'),
];
