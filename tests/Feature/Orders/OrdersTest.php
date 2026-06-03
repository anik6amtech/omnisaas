<?php

use App\Domain\AI\Actions\CaptureOrderDraft;
use App\Domain\Orders\Actions\CreateOrder;
use App\Domain\Orders\Actions\CreateOrderPaymentLink;
use App\Domain\Orders\Exceptions\InsufficientStockException;
use App\Domain\Tenancy\Context\CurrentWorkspace;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use Prism\Prism\ValueObjects\Usage;

function orderWorkspace(): Workspace
{
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create(['current_workspace_id' => $workspace->id]);
    test()->actingAs($user, 'web');
    app(CurrentWorkspace::class)->set($workspace);

    return $workspace;
}

beforeEach(fn () => config(['sslcommerz.store_id' => 'test', 'sslcommerz.store_password' => 'test']));

it('creates an order, decrements stock, and totals correctly', function () {
    $ws = orderWorkspace();
    $product = Product::factory()->recycle($ws)->create(['price' => 500, 'stock' => 10]);

    $order = app(CreateOrder::class)->execute(
        ['customer_name' => 'Sadia', 'delivery_charge' => 60],
        [['product_id' => $product->id, 'name' => $product->name, 'quantity' => 2]],
    );

    expect($order->items)->toHaveCount(1)
        ->and((float) $order->subtotal)->toBe(1000.0)
        ->and((float) $order->total)->toBe(1060.0)
        ->and($product->fresh()->stock)->toBe(8);
});

it('prevents overselling (insufficient stock)', function () {
    $ws = orderWorkspace();
    $product = Product::factory()->recycle($ws)->create(['stock' => 1]);

    expect(fn () => app(CreateOrder::class)->execute(
        [],
        [['product_id' => $product->id, 'name' => $product->name, 'quantity' => 5]],
    ))->toThrow(InsufficientStockException::class);

    expect($product->fresh()->stock)->toBe(1); // rolled back
});

it('creates an SSLCommerz payment link for an order', function () {
    Http::fake(['*/gwprocess/v4/api.php' => Http::response([
        'status' => 'SUCCESS',
        'GatewayPageURL' => 'https://sandbox.sslcommerz.com/checkout/abc',
    ])]);
    $ws = orderWorkspace();
    $order = Order::factory()->recycle($ws)->create(['total' => 1060]);

    $url = app(CreateOrderPaymentLink::class)->execute($order);

    expect($url)->toBe('https://sandbox.sslcommerz.com/checkout/abc')
        ->and(Payment::withoutGlobalScopes()->where('payable_id', $order->id)->where('status', 'pending')->exists())->toBeTrue();
});

it('settles an order via a validated IPN and is idempotent', function () {
    Http::fake([
        '*/gwprocess/v4/api.php' => Http::response(['status' => 'SUCCESS', 'GatewayPageURL' => 'https://x/y']),
        '*/validator/*' => Http::response(['status' => 'VALID']),
    ]);
    $ws = orderWorkspace();
    $order = Order::factory()->recycle($ws)->create(['total' => 1060]);
    app(CreateOrderPaymentLink::class)->execute($order);
    $payment = Payment::withoutGlobalScopes()->where('payable_id', $order->id)->sole();

    $ipn = ['tran_id' => $payment->tran_id, 'val_id' => 'VAL123', 'status' => 'VALID'];

    $this->post('/webhooks/payments/ipn', $ipn)->assertOk();

    expect($payment->fresh()->status)->toBe('paid')
        ->and(Order::withoutGlobalScopes()->find($order->id)->payment_status)->toBe('paid')
        ->and(Order::withoutGlobalScopes()->find($order->id)->status)->toBe('confirmed');

    // Duplicate IPN — idempotent no-op.
    $this->post('/webhooks/payments/ipn', $ipn)->assertOk()->assertSee('already processed');
});

it('rejects an IPN that fails gateway validation', function () {
    Http::fake([
        '*/gwprocess/v4/api.php' => Http::response(['status' => 'SUCCESS', 'GatewayPageURL' => 'https://x/y']),
        '*/validator/*' => Http::response(['status' => 'INVALID_TRANSACTION']),
    ]);
    $ws = orderWorkspace();
    $order = Order::factory()->recycle($ws)->create();
    app(CreateOrderPaymentLink::class)->execute($order);
    $payment = Payment::withoutGlobalScopes()->where('payable_id', $order->id)->sole();

    $this->post('/webhooks/payments/ipn', ['tran_id' => $payment->tran_id, 'val_id' => 'X'])->assertOk();

    expect($payment->fresh()->status)->toBe('failed')
        ->and(Order::withoutGlobalScopes()->find($order->id)->payment_status)->toBe('unpaid');
});

it('slot-fills an order draft from a conversation (structured)', function () {
    Prism::fake([
        StructuredResponseFake::make()->withStructured([
            'product' => 'Blue Jacket', 'quantity' => 1, 'customer_name' => 'Sadia',
            'phone' => '8801700000000', 'delivery_zone' => 'inside_dhaka', 'complete' => true,
        ])->withUsage(new Usage(20, 10)),
    ]);

    $draft = app(CaptureOrderDraft::class)->execute('I want 1 blue jacket, name Sadia, inside Dhaka');

    expect($draft['product'])->toBe('Blue Jacket')->and($draft['complete'])->toBeTrue();
});
