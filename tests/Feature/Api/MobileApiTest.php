<?php

use App\Domain\Messaging\Jobs\SendOutboundMessage;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

function apiSeller(): array
{
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create(['current_workspace_id' => $workspace->id]);

    return [$workspace, $user];
}

it('issues a Sanctum token on valid login', function () {
    $user = User::factory()->create(['password' => Hash::make('secret-pass')]);

    $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'secret-pass', 'device_name' => 'pixel'])
        ->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

    expect($user->tokens()->count())->toBe(1);
});

it('rejects invalid login credentials', function () {
    $user = User::factory()->create(['password' => Hash::make('secret-pass')]);

    $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'nope'])
        ->assertStatus(422);
});

it('requires authentication for the inbox API', function () {
    $this->getJson('/api/v1/conversations')->assertUnauthorized();
});

it('lists tenant-scoped conversations for the token user', function () {
    [$workspace, $user] = apiSeller();
    Sanctum::actingAs($user);
    $mine = Conversation::factory()->recycle($workspace)->create();
    Conversation::factory()->create(); // another tenant

    $this->getJson('/api/v1/conversations')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mine->id);
});

it('404s a conversation from another workspace (tenant isolation)', function () {
    [, $user] = apiSeller();
    Sanctum::actingAs($user);
    $foreign = Conversation::factory()->create(); // different workspace

    $this->getJson("/api/v1/conversations/{$foreign->id}")->assertNotFound();
});

it('sends an agent reply through the API via SendMessageAction', function () {
    Queue::fake([SendOutboundMessage::class]);
    [$workspace, $user] = apiSeller();
    Sanctum::actingAs($user);
    $conversation = Conversation::factory()->recycle($workspace)->create();

    $this->postJson("/api/v1/conversations/{$conversation->id}/reply", ['body' => 'Hi from mobile'])
        ->assertCreated()
        ->assertJsonPath('data.body', 'Hi from mobile');

    expect(Message::withoutGlobalScopes()->where('conversation_id', $conversation->id)->where('author', 'agent')->exists())->toBeTrue();
    Queue::assertPushed(SendOutboundMessage::class);
});
