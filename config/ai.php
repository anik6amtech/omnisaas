<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI provider + model routing (OpenRouter via Prism)
    |--------------------------------------------------------------------------
    | Cheap/fast model for intent classification; a stronger model for reply
    | composition; an embedding model whose dimension is LOCKED to the
    | kb_chunks.embedding column (changing it = re-embed the whole corpus).
    | The operator can override these at runtime via the settings engine (E9).
    */

    'provider' => env('PRISM_PROVIDER', 'openrouter'),

    'models' => [
        'intent' => env('AI_INTENT_MODEL', 'openai/gpt-4o-mini'),
        'generation' => env('AI_GENERATION_MODEL', 'anthropic/claude-3.5-sonnet'),
        'embedding' => env('AI_EMBEDDING_MODEL', 'openai/text-embedding-3-small'),
    ],

    'embedding_dimensions' => (int) env('AI_EMBEDDING_DIMENSIONS', 1536),

    /*
    |--------------------------------------------------------------------------
    | Guardrails
    |--------------------------------------------------------------------------
    | Below the confidence threshold (or on an escalation intent) the AI hands
    | off to a human rather than guessing. Retrieval is always tenant-scoped.
    */

    'confidence_threshold' => (float) env('AI_CONFIDENCE_THRESHOLD', 0.6),
    'retrieval_limit' => (int) env('AI_RETRIEVAL_LIMIT', 6),

    /** Intents that always escalate to a human, regardless of confidence. */
    'escalation_intents' => [
        'complaint',
        'refund',
        'legal',
        'payment_dispute',
    ],
];
