<?php

namespace App\Providers;

use App\Models\AdminUser;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        // Operators only — resolved from the admin guard (the default guard is
        // the tenant `web` guard). Local env is allowed by Horizon itself.
        Gate::define('viewHorizon', function ($user = null): bool {
            $operator = auth('admin')->user();

            return $operator instanceof AdminUser && $operator->is_active;
        });
    }
}
