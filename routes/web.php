<?php

use App\Http\Controllers\Webhooks\MetaWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
| Meta channel webhooks — one endpoint per channel type. GET answers Meta's
| verification handshake; POST is HMAC-verified before the pipeline sees it.
*/
Route::prefix('webhooks/meta')->group(function () {
    Route::get('{type}', [MetaWebhookController::class, 'verify']);
    Route::post('{type}', [MetaWebhookController::class, 'handle'])
        ->middleware('meta.webhook');
});
