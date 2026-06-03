<?php

namespace App\Domain\AI\Actions;

use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

/**
 * Deterministic order-capture slot-filling via Prism structured output:
 * product → variant → qty → name → phone → address → zone. `complete` signals
 * when every required slot is filled so the AI can call create_order.
 */
class CaptureOrderDraft
{
    /**
     * @return array<string, mixed>
     */
    public function execute(string $conversation): array
    {
        $schema = new ObjectSchema(
            name: 'order_draft',
            description: 'Structured order details slot-filled from the conversation',
            properties: [
                new StringSchema('product', 'Product the customer wants'),
                new StringSchema('variant', 'Size/colour/variant, if any'),
                new NumberSchema('quantity', 'Quantity'),
                new StringSchema('customer_name', 'Buyer name'),
                new StringSchema('phone', 'Buyer phone'),
                new StringSchema('address', 'Delivery address'),
                new StringSchema('delivery_zone', 'inside_dhaka | outside_dhaka | other'),
                new BooleanSchema('complete', 'True when all required slots are filled'),
            ],
            requiredFields: ['product', 'quantity', 'complete'],
        );

        $response = Prism::structured()
            ->using(config('ai.provider'), config('ai.models.generation'))
            ->withSchema($schema)
            ->withPrompt("Extract the order details from this conversation:\n\n{$conversation}")
            ->asStructured();

        return $response->structured ?? [];
    }
}
