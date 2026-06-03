<?php

use App\Domain\Tenancy\Actions\SwitchWorkspace;
use App\Domain\Tenancy\Services\AgencyService;
use App\Livewire\Agency\AgencyPage;
use App\Models\UsageEvent;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\UniqueConstraintViolationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('models white-label branding and agency hierarchy', function () {
    $agency = Workspace::factory()->create(['branding' => ['name' => 'ReplyHub', 'color' => 'indigo']]);
    $child = Workspace::factory()->create(['parent_id' => $agency->id]);

    expect($agency->brandName())->toBe('ReplyHub')
        ->and($agency->brandColor())->toBe('indigo')
        ->and($agency->fresh()->isAgency())->toBeTrue()
        ->and($child->parent->is($agency))->toBeTrue()
        ->and(Workspace::factory()->create()->brandName())->toBe('OmniReply'); // default
});

it('lets a member switch, lets an agency parent-member drop into a sub-account, and denies others', function () {
    $agency = Workspace::factory()->create();
    $child = Workspace::factory()->create(['parent_id' => $agency->id]);
    $unrelated = Workspace::factory()->create();

    $user = User::factory()->create();
    $agency->users()->attach($user, ['role' => 'owner']);

    $switch = app(SwitchWorkspace::class);

    $switch->execute($user, $agency);
    expect($user->fresh()->current_workspace_id)->toBe($agency->id);

    $switch->execute($user, $child); // agency parent-member -> sub-account
    expect($user->fresh()->current_workspace_id)->toBe($child->id);

    expect(fn () => $switch->execute($user, $unrelated))->toThrow(HttpException::class);
});

it('aggregates AI usage across the agency and its sub-accounts', function () {
    $agency = Workspace::factory()->create();
    $child = Workspace::factory()->create(['parent_id' => $agency->id]);
    UsageEvent::query()->create(['workspace_id' => $agency->id, 'type' => 'ai_reply', 'tokens' => 1]);
    UsageEvent::query()->create(['workspace_id' => $child->id, 'type' => 'ai_reply', 'tokens' => 1]);
    UsageEvent::query()->create(['workspace_id' => Workspace::factory()->create()->id, 'type' => 'ai_reply', 'tokens' => 1]);

    expect(app(AgencyService::class)->aggregateAiReplies($agency))->toBe(2)
        ->and(app(AgencyService::class)->subWorkspaces($agency))->toHaveCount(1);
});

it('switches sub-account from the agency page', function () {
    $agency = Workspace::factory()->create();
    $child = Workspace::factory()->create(['parent_id' => $agency->id]);
    $user = User::factory()->create(['current_workspace_id' => $agency->id]);
    $agency->users()->attach($user, ['role' => 'owner']);
    test()->actingAs($user, 'web');

    Livewire::test(AgencyPage::class)
        ->call('switchTo', $child->id)
        ->assertRedirect(route('app.inbox'));

    expect($user->fresh()->current_workspace_id)->toBe($child->id);
});

it('enforces unique custom domains', function () {
    Workspace::factory()->create(['custom_domain' => 'shop.example.com']);

    expect(fn () => Workspace::factory()->create(['custom_domain' => 'shop.example.com']))
        ->toThrow(UniqueConstraintViolationException::class);
});
