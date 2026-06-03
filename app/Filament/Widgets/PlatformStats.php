<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use App\Models\UsageEvent;
use App\Models\Workspace;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Operator dashboard KPIs across ALL tenants (no workspace context in the admin
 * panel ⇒ the global scope is a no-op ⇒ platform-wide figures).
 */
class PlatformStats extends StatsOverviewWidget
{
    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $billable = ['active', 'trialing', 'past_due', 'grace'];

        $mrr = Subscription::query()
            ->whereIn('subscriptions.status', $billable)
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->sum('plans.price_bdt');

        return [
            Stat::make('Tenants', (string) Workspace::query()->count()),
            Stat::make('Active subscriptions', (string) Subscription::query()->whereIn('status', $billable)->count()),
            Stat::make('MRR (BDT)', number_format((float) $mrr)),
            Stat::make('AI replies this month', (string) UsageEvent::query()
                ->where('type', 'ai_reply')
                ->where('created_at', '>=', now()->startOfMonth())
                ->count()),
        ];
    }
}
