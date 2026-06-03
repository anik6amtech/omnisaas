<?php

use App\Domain\Tenancy\Context\CurrentWorkspace;
use App\Livewire\Settings\ChannelsPage;
use App\Models\Channel;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function channelSeller(): Workspace
{
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create(['current_workspace_id' => $workspace->id]);
    $workspace->users()->attach($user, ['role' => 'owner']);
    test()->actingAs($user, 'web');
    app(CurrentWorkspace::class)->set($workspace);

    return $workspace;
}

it('connects a channel for the workspace with an encrypted token', function () {
    $ws = channelSeller();

    Livewire::test(ChannelsPage::class)
        ->set('type', 'whatsapp')
        ->set('external_id', 'PHONE123')
        ->set('name', 'My WhatsApp')
        ->set('access_token', 'secret-token')
        ->call('connect')
        ->assertHasNoErrors();

    $channel = Channel::withoutGlobalScopes()->where('external_id', 'PHONE123')->sole();
    expect($channel->workspace_id)->toBe($ws->id)
        ->and($channel->access_token)->toBe('secret-token')
        ->and($channel->status)->toBe('active');

    // Token is encrypted at rest.
    expect(DB::table('channels')->where('id', $channel->id)->value('access_token'))->not->toBe('secret-token');
});

it('connects without route middleware setting the workspace (Livewire update path)', function () {
    // Reproduces the browser bug: Livewire's /livewire/update AJAX call bypasses
    // the `workspace` route middleware, so CurrentWorkspace is never set by the
    // route. The InteractsWithWorkspace boot hook must establish it from the
    // authenticated seller — otherwise workspace_id is null on insert.
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create(['current_workspace_id' => $workspace->id]);
    $workspace->users()->attach($user, ['role' => 'owner']);
    test()->actingAs($user, 'web');
    // NOTE: deliberately NOT calling CurrentWorkspace::set() here.

    Livewire::test(ChannelsPage::class)
        ->set('type', 'whatsapp')
        ->set('external_id', 'NOMIDDLEWARE')
        ->set('access_token', 'tok')
        ->call('connect')
        ->assertHasNoErrors();

    $channel = Channel::withoutGlobalScopes()->where('external_id', 'NOMIDDLEWARE')->sole();
    expect($channel->workspace_id)->toBe($workspace->id);
});

it('validates required fields', function () {
    channelSeller();

    Livewire::test(ChannelsPage::class)
        ->set('external_id', '')
        ->set('access_token', '')
        ->call('connect')
        ->assertHasErrors(['external_id', 'access_token']);
});

it('lists only the workspace channels', function () {
    $ws = channelSeller();
    $mine = Channel::factory()->recycle($ws)->create();
    Channel::factory()->create(); // other workspace

    $ids = Livewire::test(ChannelsPage::class)->instance()->channels->pluck('id');
    expect($ids)->toContain($mine->id)->toHaveCount(1);
});

it('disconnects a channel', function () {
    $ws = channelSeller();
    $channel = Channel::factory()->recycle($ws)->create();

    Livewire::test(ChannelsPage::class)->call('disconnect', $channel->id);

    expect(Channel::withoutGlobalScopes()->whereKey($channel->id)->exists())->toBeFalse();
});

it('shows a channel-specific setup guide that updates with the selection', function () {
    channelSeller();

    Livewire::test(ChannelsPage::class)
        ->assertSet('type', 'whatsapp')
        ->assertSee('Phone number ID', false)
        ->set('type', 'instagram')
        ->assertSee('comment-to-DM', false)
        ->set('type', 'facebook')
        ->assertSee('Page', false);
});

it('rejects connecting a channel already owned by another workspace', function () {
    Channel::factory()->whatsapp()->create(['external_id' => 'TAKEN']); // another workspace
    channelSeller();

    Livewire::test(ChannelsPage::class)
        ->set('type', 'whatsapp')
        ->set('external_id', 'TAKEN')
        ->set('access_token', 'tok')
        ->call('connect')
        ->assertHasErrors('external_id');
});

it('stores the per-channel Meta app secret encrypted, with .env fallback', function () {
    $ws = channelSeller();

    Livewire::test(ChannelsPage::class)
        ->set('type', 'facebook')
        ->set('external_id', 'PAGE7')
        ->set('access_token', 'tok')
        ->set('app_id', 'APP1')
        ->set('app_secret', 'my-app-secret')
        ->call('connect')
        ->assertHasNoErrors();

    $channel = Channel::withoutGlobalScopes()->where('external_id', 'PAGE7')->sole();

    expect($channel->effectiveAppSecret())->toBe('my-app-secret')
        ->and(DB::table('channels')->where('id', $channel->id)->value('app_secret'))->not->toBe('my-app-secret')
        // No per-channel verify token ⇒ the channel verifies against the
        // workspace's tenant-wide token (shown on the page).
        ->and($channel->effectiveVerifyToken())->toBe($ws->fresh()->webhook_verify_token);

    // A channel with no own app secret falls back to the shared app's (.env).
    config(['services.meta.app_secret' => 'shared-secret']);
    $shared = Channel::factory()->whatsapp()->create(['app_secret' => null]);
    expect($shared->effectiveAppSecret())->toBe('shared-secret');
});

it('issues a verify token unique to each workspace (tenant-wise)', function () {
    $wsA = channelSeller();
    $tokenA = Livewire::test(ChannelsPage::class)->instance()->verifyToken();

    expect($tokenA)->toStartWith('omr_')
        ->and($wsA->fresh()->webhook_verify_token)->toBe($tokenA); // generated + persisted on view

    // A different tenant sees a different token.
    channelSeller();
    $tokenB = Livewire::test(ChannelsPage::class)->instance()->verifyToken();
    expect($tokenB)->not->toBe($tokenA);
});
