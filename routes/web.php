<?php

use App\Http\Controllers\Webhooks\MetaWebhookController;
use App\Livewire\Auth\Login;
use App\Livewire\Catalog\CatalogPage;
use App\Livewire\Inbox\InboxPage;
use App\Livewire\Knowledge\KnowledgePage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
| Tenant plane (/app) — sellers on the web guard. The `workspace` middleware
| binds the active workspace so every query is tenant-isolated.
*/
Route::middleware('guest:web')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware(['auth:web', 'workspace'])->prefix('app')->group(function () {
    Route::redirect('/', '/app/inbox');
    Route::get('/inbox', InboxPage::class)->name('app.inbox');
    Route::get('/catalog', CatalogPage::class)->name('app.catalog');
    Route::get('/knowledge', KnowledgePage::class)->name('app.knowledge');

    Route::post('/logout', function () {
        Auth::guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect('/login');
    })->name('app.logout');
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
