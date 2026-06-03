<?php

namespace App\Domain\Messaging\Data;

/**
 * A pre-approved template message for out-of-window re-engagement. Marketing
 * templates are charged (country-priced); utility/service are free in-window.
 * Stub — finalized in E3.
 */
final readonly class TemplateMessage
{
    /**
     * @param  array<string, mixed>  $variables  Template placeholder values.
     */
    public function __construct(
        public string $templateName,
        public array $variables = [],
        public string $category = 'utility', // marketing | utility | authentication | service
    ) {}
}
