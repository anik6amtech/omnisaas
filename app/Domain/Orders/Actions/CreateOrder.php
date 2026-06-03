<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates an order with its items and decrements stock atomically. The whole
 * thing runs in one transaction with a row lock on each product to prevent
 * overselling under concurrency. Run with the workspace context set.
 */
class CreateOrder
{
    /**
     * @param  array<string, mixed>  $details
     * @param  array<int, array{product_id?: ?string, name: string, quantity: int, unit_price?: float, variant?: ?string}>  $items
     */
    public function execute(array $details, array $items, ?string $conversationId = null, ?string $customerId = null): Order
    {
        return DB::transaction(function () use ($details, $items, $conversationId, $customerId): Order {
            $order = Order::query()->create([
                'reference' => 'ORD-'.strtoupper(Str::random(8)),
                'conversation_id' => $conversationId,
                'customer_id' => $customerId,
                'customer_name' => $details['customer_name'] ?? null,
                'customer_phone' => $details['customer_phone'] ?? null,
                'address' => $details['address'] ?? null,
                'delivery_zone' => $details['delivery_zone'] ?? null,
                'delivery_charge' => (float) ($details['delivery_charge'] ?? 0),
                'currency' => $details['currency'] ?? 'BDT',
            ]);

            $subtotal = 0.0;

            foreach ($items as $item) {
                $quantity = (int) $item['quantity'];
                $unitPrice = (float) ($item['unit_price'] ?? 0);

                if (! empty($item['product_id'])) {
                    $product = Product::query()->whereKey($item['product_id'])->lockForUpdate()->first();

                    if ($product !== null) {
                        if ($product->stock < $quantity) {
                            throw new InsufficientStockException($product->name);
                        }

                        $product->decrement('stock', $quantity);
                        $unitPrice = (float) $product->price;
                    }
                }

                $lineTotal = $unitPrice * $quantity;
                $subtotal += $lineTotal;

                $order->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'name' => $item['name'],
                    'variant' => $item['variant'] ?? null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);
            }

            $order->update([
                'subtotal' => $subtotal,
                'total' => $subtotal + (float) $order->delivery_charge,
            ]);

            return $order->refresh();
        });
    }
}
