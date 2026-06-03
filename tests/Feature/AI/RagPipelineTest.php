<?php

use App\Domain\AI\Actions\ClassifyIntent;
use App\Domain\AI\Actions\ComposeReply;
use App\Domain\AI\Jobs\GenerateAiReply;
use App\Domain\AI\Services\EmbeddingService;
use App\Domain\Billing\Services\Entitlements;
use App\Domain\Inbox\Actions\EscalateConversation;
use App\Domain\Inbox\Enums\ConversationStatus;
use App\Domain\Inbox\Events\ConversationEscalated;
use App\Domain\Knowledge\Jobs\IndexKnowledge;
use App\Domain\Knowledge\Services\KnowledgeBase;
use App\Domain\Knowledge\Services\TextChunker;
use App\Domain\Messaging\Actions\SendMessageAction;
use App\Domain\Messaging\Enums\MessageAuthor;
use App\Domain\Messaging\Enums\MessageDirection;
use App\Domain\Messaging\Jobs\SendOutboundMessage;
use App\Domain\Tenancy\Context\CurrentWorkspace;
use App\Models\Conversation;
use App\Models\KbChunk;
use App\Models\KnowledgeDocument;
use App\Models\Message;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\EmbeddingsResponseFake;
use Prism\Prism\Testing\StructuredResponseFake;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\ValueObjects\Embedding;
use Prism\Prism\ValueObjects\EmbeddingsUsage;
use Prism\Prism\ValueObjects\Usage;

/** A 1536-dim one-hot vector. */
function vec(int $hot): array
{
    $v = array_fill(0, 1536, 0.0);
    $v[$hot] = 1.0;

    return $v;
}

function fakeEmbedding(int $hot): EmbeddingsResponseFake
{
    return EmbeddingsResponseFake::make()
        ->withEmbeddings([Embedding::fromArray(vec($hot))])
        ->withUsage(new EmbeddingsUsage(5));
}

it('stores and tenant-scoped cosine-searches the pgvector store', function () {
    $kb = app(KnowledgeBase::class);
    $doc = KnowledgeDocument::factory()->create();
    app(CurrentWorkspace::class)->set($doc->workspace_id);

    $kb->store($doc, 'Delivery inside Dhaka is 60 taka', vec(0));
    $kb->store($doc, 'We accept bKash and Nagad', vec(500));

    $results = $kb->search($doc->workspace_id, vec(0), 2);

    expect($results)->toHaveCount(2)
        ->and($results[0]->content)->toBe('Delivery inside Dhaka is 60 taka')  // nearest
        ->and((float) $results[0]->distance)->toBeLessThan((float) $results[1]->distance);
});

it('does not leak knowledge across workspaces', function () {
    $kb = app(KnowledgeBase::class);
    $a = KnowledgeDocument::factory()->create();
    $b = KnowledgeDocument::factory()->create();

    $kb->store($a, 'Workspace A secret', vec(1));
    $kb->store($b, 'Workspace B secret', vec(1));

    expect($kb->search($a->workspace_id, vec(1), 10))->toHaveCount(1);
});

it('indexes a document: chunks, embeds, stores, marks indexed', function () {
    Prism::fake([fakeEmbedding(0)]); // one chunk -> one embedding

    $doc = KnowledgeDocument::factory()->create(['content' => 'Short FAQ answer.', 'status' => 'pending']);

    (new IndexKnowledge($doc))->handle(
        app(TextChunker::class),
        app(EmbeddingService::class),
        app(KnowledgeBase::class),
        app(CurrentWorkspace::class),
    );

    expect($doc->fresh()->status)->toBe('indexed')
        ->and(KbChunk::withoutGlobalScopes()->where('document_id', $doc->id)->count())->toBe(1);
});

it('composes a grounded reply and sends it when confident', function () {
    Queue::fake([SendOutboundMessage::class]);
    Prism::fake([
        StructuredResponseFake::make()
            ->withStructured(['intent' => 'price', 'confidence' => 0.92, 'language' => 'en'])
            ->withUsage(new Usage(10, 5)),
        fakeEmbedding(0),
        TextResponseFake::make()->withText('The price is 1200 BDT.')->withUsage(new Usage(50, 30)),
    ]);

    $conversation = Conversation::factory()->create();
    Message::factory()->create([
        'workspace_id' => $conversation->workspace_id,
        'conversation_id' => $conversation->id,
        'direction' => MessageDirection::In,
        'body' => 'eta dam koto?',
    ]);

    runGenerate($conversation);

    $reply = Message::withoutGlobalScopes()->where('direction', MessageDirection::Out->value)->sole();
    expect($reply->body)->toBe('The price is 1200 BDT.')
        ->and($reply->author)->toBe(MessageAuthor::Ai)
        ->and($reply->status)->toBe('queued');
    Queue::assertPushed(SendOutboundMessage::class);
});

it('escalates to a human on low confidence (no reply sent)', function () {
    Event::fake([ConversationEscalated::class]);
    Prism::fake([
        StructuredResponseFake::make()
            ->withStructured(['intent' => 'product_details', 'confidence' => 0.3, 'language' => 'en'])
            ->withUsage(new Usage(10, 5)),
    ]);

    $conversation = Conversation::factory()->create();
    Message::factory()->create([
        'workspace_id' => $conversation->workspace_id,
        'conversation_id' => $conversation->id,
        'direction' => MessageDirection::In,
        'body' => 'something ambiguous',
    ]);

    runGenerate($conversation);

    expect($conversation->fresh()->status)->toBe(ConversationStatus::NeedsHuman)
        ->and(Message::withoutGlobalScopes()->where('direction', MessageDirection::Out->value)->count())->toBe(0);
    Event::assertDispatched(ConversationEscalated::class);
});

it('escalates on a complaint intent regardless of confidence', function () {
    Event::fake([ConversationEscalated::class]);
    Prism::fake([
        StructuredResponseFake::make()
            ->withStructured(['intent' => 'complaint', 'confidence' => 0.99, 'language' => 'en'])
            ->withUsage(new Usage(10, 5)),
    ]);

    $conversation = Conversation::factory()->create();
    Message::factory()->create([
        'workspace_id' => $conversation->workspace_id,
        'conversation_id' => $conversation->id,
        'direction' => MessageDirection::In,
        'body' => 'my order is late and I am angry',
    ]);

    runGenerate($conversation);

    expect($conversation->fresh()->status)->toBe(ConversationStatus::NeedsHuman);
    Event::assertDispatched(ConversationEscalated::class, fn (ConversationEscalated $e) => $e->reason === 'complaint');
});

function runGenerate(Conversation $conversation): void
{
    (new GenerateAiReply($conversation))->handle(
        app(ClassifyIntent::class),
        app(ComposeReply::class),
        app(SendMessageAction::class),
        app(EscalateConversation::class),
        app(Entitlements::class),
        app(CurrentWorkspace::class),
    );
}
