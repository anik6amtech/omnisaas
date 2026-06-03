<?php

namespace App\Events;

use App\Models\Workspace;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Foundation smoke-test event proving the Reverb broadcast path end-to-end.
 * Broadcasts on the tenant's private `workspace.{id}` channel — the same
 * channel family the live inbox will use in E5 (workspace.{id}.inbox, etc.).
 */
class WorkspacePinged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Workspace $workspace,
        public string $message = 'pong',
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('workspace.'.$this->workspace->getKey()),
        ];
    }

    public function broadcastAs(): string
    {
        return 'workspace.pinged';
    }

    /**
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return [
            'workspace_id' => (string) $this->workspace->getKey(),
            'message' => $this->message,
        ];
    }
}
