<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConversationController;
use Illuminate\Support\Facades\Route;

/*
| Versioned mobile API (Sanctum). The `workspace` middleware binds the active
| workspace from the token's user, so every query is tenant-isolated — the same
| guarantee as the web plane. Realtime: the Flutter app authorizes private
| Reverb channels via the Sanctum-guarded broadcasting auth endpoint.
*/
Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'workspace'])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/conversations', [ConversationController::class, 'index']);
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
        Route::post('/conversations/{conversation}/reply', [ConversationController::class, 'reply']);
    });
});
