<?php

use App\Domain\AI\Actions\ClassifyIntent;
use App\Domain\AI\Actions\ComposeReply;
use App\Domain\AI\Jobs\GenerateAiReply;
use App\Domain\Billing\Actions\CreateSubscriptionPaymentLink;
use App\Domain\Billing\Actions\StartSubscription;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Services\Entitlements;
use App\Domain\Billing\Services\SubscriptionLifecycle;
use App\Domain\Inbox\Actions\EscalateConversation;
use App\Domain\Inbox\Events\ConversationEscalated;
use App\Domain\Messaging\Actions\SendMessageAction;
use App\Domain\Messaging\Enums\MessageDirection;
use App\Domain\Tenancy\Context\CurrentWorkspace;
use App\Models\Conversation;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

function billingWorkspace(): Workspace
{
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create(['current_workspace_id' => $workspace->id]);
    test()->actingAs($user, 'web');
    app(CurrentWorkspace::class)->set($workspace);

    return $workspace;
}

beforeEach(fn () => config(['sslcommerz.store_id' => 'test', 'sslcommerz.store_password' => 'test']));

it('starts a trial subscription with access and no invoice', function () {
    billingWorkspace();
    $plan = Plan::factory()->withTrial(14)->create();

    $subscription = app(StartSubscription::class)->execute($plan);

    expect($subscription->status)->toBe(SubscriptionStatus::Trialing)
        ->and($subscription->grantsAccess())->toBeTrue()
        ->and($subscription->invoices()->count())->toBe(0);
});

it('raises a first invoice for a non-trial plan and activates on settled IPN', function () {
    Http::fake([
        '*/gwprocess/v4/api.php' => Http::response(['status' => 'SUCCESS', 'GatewayPageURL' => 'https://x/y']),
        '*/validator/*' => Http::response(['status' => 'VALID']),
    ]);
    $ws = billingWorkspace();
    $plan = Plan::factory()->create(['price_bdt' => 1000]);

    $start = app(StartSubscription::class);
    $subscription = $start->execute($plan);
    $invoice = $start->pendingInvoice($subscription);

    expect($invoice)->not->toBeNull();

    app(CreateSubscriptionPaymentLink::class)->execute($invoice);
    $payment = Payment::withoutGlobalScopes()->where('payable_id', $invoice->id)->sole();

    $this->post('/webhooks/payments/ipn', ['tran_id' => $payment->tran_id, 'val_id' => 'V1'])->assertOk();

    expect(Invoice::withoutGlobalScopes()->find($invoice->id)->status)->toBe('paid')
        ->and(Subscription::withoutGlobalScopes()->find($subscription->id)->status->value)->toBe('active');
});

it('enforces plan feature + numeric entitlements', function () {
    $ws = billingWorkspace();
    $plan = Plan::factory()->limits(['ai_replies' => 2])->create([
        'entitlements' => ['limits' => ['ai_replies' => 2], 'features' => ['comment_to_dm']],
    ]);
    Subscription::factory()->recycle($ws)->create(['plan_id' => $plan->id, 'status' => 'active']);

    $entitlements = app(Entitlements::class);

    expect($entitlements->allowsFeature('comment_to_dm'))->toBeTrue()
        ->and($entitlements->allowsFeature('voice_notes'))->toBeFalse()
        ->and($entitlements->withinLimit('ai_replies', 1))->toBeTrue()
        ->and($entitlements->withinLimit('ai_replies', 2))->toBeFalse();
});

it('progresses dunning: past_due -> grace -> suspended, and cancels', function () {
    $ws = billingWorkspace();
    $subscription = Subscription::factory()->recycle($ws)->create(['status' => 'active']);
    $lifecycle = app(SubscriptionLifecycle::class);

    $lifecycle->recordFailedCharge($subscription);
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::PastDue);

    $lifecycle->recordFailedCharge($subscription);
    $lifecycle->recordFailedCharge($subscription); // 3rd -> grace
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Grace);

    $subscription->update(['grace_ends_at' => now()->subDay()]);
    $lifecycle->endGraceIfExpired($subscription);
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Suspended);

    $lifecycle->cancel($subscription);
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Cancelled);
});

it('escalates AI replies when the monthly allowance is exhausted', function () {
    Event::fake([ConversationEscalated::class]);
    $ws = billingWorkspace();
    $plan = Plan::factory()->create(['entitlements' => ['limits' => ['ai_replies' => 1], 'features' => []]]);
    Subscription::factory()->recycle($ws)->create(['plan_id' => $plan->id, 'status' => 'active']);

    $conversation = Conversation::factory()->recycle($ws)->create();
    Message::factory()->recycle($ws)->create([
        'conversation_id' => $conversation->id,
        'direction' => MessageDirection::In,
        'body' => 'price?',
    ]);
    // One AI reply already used this month -> at the limit of 1.
    UsageEvent::query()->create(['workspace_id' => $ws->id, 'type' => 'ai_reply', 'tokens' => 10]);

    (new GenerateAiReply($conversation))->handle(
        app(ClassifyIntent::class),
        app(ComposeReply::class),
        app(SendMessageAction::class),
        app(EscalateConversation::class),
        app(Entitlements::class),
        app(CurrentWorkspace::class),
    );

    expect($conversation->fresh()->status->value)->toBe('needs_human');
    Event::assertDispatched(ConversationEscalated::class, fn (ConversationEscalated $e) => $e->reason === 'ai_quota_exceeded');
});
