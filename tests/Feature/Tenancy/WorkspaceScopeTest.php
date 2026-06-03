<?php

use App\Domain\Tenancy\Context\CurrentWorkspace;
use App\Models\Channel;
use App\Models\Workspace;

it('constrains tenant-scoped models to the active workspace', function () {
    $workspace = Workspace::factory()->create();
    app(CurrentWorkspace::class)->set($workspace);

    $query = Channel::query();

    expect($query->toSql())->toContain('"channels"."workspace_id"')
        ->and($query->getBindings())->toContain($workspace->getKey());
});

it('applies no workspace constraint when none is active', function () {
    app(CurrentWorkspace::class)->forget();

    expect(Channel::query()->toSql())->not->toContain('workspace_id');
});

it('resolves the same CurrentWorkspace instance within a request (scoped binding)', function () {
    expect(app(CurrentWorkspace::class))->toBe(app(CurrentWorkspace::class));
});
