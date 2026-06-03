<?php

namespace App\Domain\AI\Actions;

use App\Domain\AI\Data\IntentResult;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

/**
 * Classifies an inbound message over the f-commerce taxonomy using the cheap/
 * fast model and Prism structured output (deterministic, typed).
 */
class ClassifyIntent
{
    public function execute(string $message): IntentResult
    {
        $schema = new ObjectSchema(
            name: 'intent_classification',
            description: 'Classification of an f-commerce customer message',
            properties: [
                new EnumSchema('intent', 'The primary intent', [
                    'greeting', 'price', 'availability', 'product_details', 'delivery',
                    'order', 'order_status', 'payment', 'complaint', 'refund',
                    'bargaining', 'out_of_scope',
                ]),
                new NumberSchema('confidence', 'Confidence from 0 to 1'),
                new StringSchema('language', 'Detected language: bn | bn_rom | en'),
            ],
            requiredFields: ['intent', 'confidence', 'language'],
        );

        $response = Prism::structured()
            ->using(config('ai.provider'), config('ai.models.intent'))
            ->withSchema($schema)
            ->withPrompt("Classify this f-commerce customer message:\n\n{$message}")
            ->asStructured();

        /** @var array<string, mixed> $data */
        $data = $response->structured ?? [];

        return new IntentResult(
            intent: (string) ($data['intent'] ?? 'out_of_scope'),
            confidence: (float) ($data['confidence'] ?? 0.0),
            language: (string) ($data['language'] ?? 'en'),
        );
    }
}
