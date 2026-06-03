<?php

use App\Events\WorkspacePinged;
use App\Models\Workspace;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Event;

it('is a broadcastable event', function () {
    $event = new WorkspacePinged(Workspace::factory()->make());

    expect($event)->toBeInstanceOf(ShouldBroadcast::class)
        ->and($event->broadcastAs())->toBe('workspace.pinged');
});

it('broadcasts on the private workspace channel when dispatched', function () {
    Event::fake([WorkspacePinged::class]);

    $workspace = Workspace::factory()->create();

    WorkspacePinged::dispatch($workspace, 'pong');

    Event::assertDispatched(WorkspacePinged::class, function (WorkspacePinged $event) use ($workspace) {
        $channels = collect($event->broadcastOn());

        return $event->workspace->is($workspace)
            && $event->message === 'pong'
            && $channels->contains(
                fn ($channel) => $channel instanceof PrivateChannel
                    && $channel->name === 'private-workspace.'.$workspace->getKey()
            );
    });
});
