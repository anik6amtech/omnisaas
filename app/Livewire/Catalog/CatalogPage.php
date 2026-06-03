<?php

namespace App\Livewire\Catalog;

use App\Domain\Catalog\Actions\ImportCatalogCsv;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\Product;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class CatalogPage extends Component
{
    use InteractsWithWorkspace;
    use WithFileUploads;

    public $csv;

    public ?int $imported = null;

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function products(): Collection
    {
        return Product::query()->orderByDesc('created_at')->limit(100)->get();
    }

    public function import(ImportCatalogCsv $importer): void
    {
        $this->validate(['csv' => 'required|file|max:2048']);

        $this->imported = $importer->execute((string) file_get_contents($this->csv->getRealPath()));
        $this->csv = null;
        unset($this->products);
    }

    public function render()
    {
        return view('livewire.catalog.catalog-page');
    }
}
