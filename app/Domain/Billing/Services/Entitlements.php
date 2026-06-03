<?php

namespace App\Domain\Billing\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;

/**
 * The entitlements engine the whole app calls — reads the active workspace's
 * plan record (DB-defined, panel-editable), never hardcoded constants. Limits:
 * null = unlimited. Tenant-scoped via the global scope.
 */
class Entitlements
{
    public function current(): ?Subscription
    {
        return Subscription::query()->with('plan')->latest()->first();
    }

    public function plan(): ?Plan
    {
        return $this->current()?->plan;
    }

    public function hasAccess(): bool
    {
        return (bool) $this->current()?->grantsAccess();
    }

    public function allowsFeature(string $feature): bool
    {
        $subscription = $this->current();

        return $subscription !== null
            && $subscription->grantsAccess()
            && $subscription->plan->allowsFeature($feature);
    }

    public function limit(string $key): ?int
    {
        return $this->plan()?->limit($key);
    }

    public function withinLimit(string $key, int $current): bool
    {
        $limit = $this->limit($key);

        return $limit === null || $current < $limit; // null = unlimited
    }

    public function aiRepliesThisPeriod(): int
    {
        return UsageEvent::query()
            ->where('type', 'ai_reply')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    /** No subscription / no limit ⇒ unlimited (allowed). */
    public function canSendAiReply(): bool
    {
        return $this->withinLimit('ai_replies', $this->aiRepliesThisPeriod());
    }
}
