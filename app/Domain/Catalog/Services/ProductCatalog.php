<?php

namespace App\Domain\Catalog\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Relational catalog lookup — the AI reads prices/stock from HERE (never the
 * vector store) so a reply can never quote a stale or hallucinated number.
 * Tenant-scoped by the workspace global scope.
 */
class ProductCatalog
{
    /**
     * @return Collection<int, Product>
     */
    public function search(string $query, int $limit = 5): Collection
    {
        return Product::query()
            ->where('active', true)
            ->where('name', 'ilike', '%'.trim($query).'%')
            ->limit($limit)
            ->get();
    }

    /** Authoritative catalog facts as grounding text for the AI. */
    public function factsFor(string $query, int $limit = 5): string
    {
        return $this->search($query, $limit)
            ->map(fn (Product $p): string => sprintf(
                '%s — %s %s — %s',
                $p->name,
                $p->price,
                $p->currency,
                $p->inStock() ? "in stock ({$p->stock})" : 'out of stock',
            ))
            ->implode("\n");
    }
}
