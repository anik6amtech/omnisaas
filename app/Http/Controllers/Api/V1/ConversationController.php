<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Messaging\Actions\SendMessageAction;
use App\Domain\Messaging\Enums\MessageAuthor;
use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Mobile inbox API. Route-model binding is tenant-isolated by the workspace
 * global scope (a conversation from another tenant 404s). Sends go through the
 * same SendMessageAction as the web inbox — zero logic duplication.
 */
class ConversationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ConversationResource::collection(
            Conversation::query()
                ->with(['customer', 'channel'])
                ->orderByDesc('last_message_at')
                ->paginate(20),
        );
    }

    public function show(string $conversation): JsonResource
    {
        // Query inside the action (workspace already bound) so the global scope
        // guarantees tenant isolation regardless of middleware ordering.
        $model = Conversation::query()
            ->with(['customer', 'channel', 'messages'])
            ->findOrFail($conversation);

        return ConversationResource::make($model);
    }

    public function reply(Request $request, string $conversation, SendMessageAction $send): JsonResource
    {
        $request->validate(['body' => 'required|string']);

        $model = Conversation::query()->findOrFail($conversation);

        $message = $send->execute(
            $model,
            (string) $request->input('body'),
            MessageAuthor::Agent,
            $request->user()->id,
        );

        return MessageResource::make($message);
    }
}
