<?php

namespace Database\Factories;

use App\Models\KnowledgeDocument;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeDocument>
 */
class KnowledgeDocumentFactory extends Factory
{
    protected $model = KnowledgeDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'source_type' => 'faq',
            'title' => fake()->sentence(3),
            'content' => fake()->paragraphs(3, true),
            'status' => 'pending',
        ];
    }
}
