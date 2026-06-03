<?php

namespace App\Livewire\Knowledge;

use App\Domain\Knowledge\Actions\IngestKnowledge;
use App\Models\KnowledgeDocument;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
class KnowledgePage extends Component
{
    public string $title = '';

    #[Validate('required|string|min:3')]
    public string $content = '';

    public string $sourceType = 'faq';

    /**
     * @return Collection<int, KnowledgeDocument>
     */
    #[Computed]
    public function documents(): Collection
    {
        return KnowledgeDocument::query()->orderByDesc('created_at')->limit(50)->get();
    }

    public function ingest(IngestKnowledge $ingest): void
    {
        $this->validate();

        $ingest->execute($this->content, $this->sourceType, $this->title ?: null);

        $this->reset('title', 'content');
        unset($this->documents);
    }

    public function render()
    {
        return view('livewire.knowledge.knowledge-page');
    }
}
