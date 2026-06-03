<?php

namespace App\Providers;

use App\Domain\Tenancy\Context\CurrentWorkspace;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Active workspace, per request/job. `scoped` => Octane flushes it
        // between requests, so tenant context never leaks across requests.
        $this->app->scoped(CurrentWorkspace::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceScheme('https');
    }
}
