<?php

use App\Models\AdminUser;
use App\Models\Plan;
use Database\Seeders\PlanSeeder;

it('seeds the default SaaS tiers with entitlements', function () {
    (new PlanSeeder)->run();

    expect(Plan::query()->count())->toBe(4)
        ->and(Plan::query()->where('slug', 'business')->first()->allowsFeature('instagram'))->toBeTrue()
        ->and(Plan::query()->where('slug', 'free')->first()->limit('ai_replies'))->toBe(200);
});

it('lets an operator reach the Plans and Subscriptions resources', function () {
    $operator = AdminUser::factory()->create();

    $this->actingAs($operator, 'admin')->get('/admin/plans')->assertOk();
    $this->actingAs($operator, 'admin')->get('/admin/subscriptions')->assertOk();
});

it('renders the operator dashboard with platform stats', function () {
    $operator = AdminUser::factory()->create();

    $this->actingAs($operator, 'admin')->get('/admin')->assertOk();
});

it('keeps the control plane behind the admin guard', function () {
    $this->get('/admin/plans')->assertRedirect();
});
