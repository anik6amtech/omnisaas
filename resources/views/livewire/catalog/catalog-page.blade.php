<div class="mx-auto max-w-4xl p-6">
    <h1 class="mb-4 text-xl font-semibold text-gray-900">Catalog</h1>

    <form wire:submit="import" class="mb-6 flex items-end gap-3 rounded-xl border border-gray-200 bg-white p-4">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700">Import products (CSV: name, sku, price, stock, description)</label>
            <input type="file" wire:model="csv" accept=".csv,text/csv"
                class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-amber-50 file:px-3 file:py-2 file:text-amber-700" />
            @error('csv') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 font-medium text-white hover:bg-amber-700">Import</button>
    </form>

    @if ($imported !== null)
        <p class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-700">Imported {{ $imported }} products.</p>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-2">Name</th><th class="px-4 py-2">SKU</th>
                    <th class="px-4 py-2">Price</th><th class="px-4 py-2">Stock</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($this->products as $product)
                    <tr wire:key="p-{{ $product->id }}">
                        <td class="px-4 py-2 font-medium text-gray-900">{{ $product->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $product->sku }}</td>
                        <td class="px-4 py-2">{{ $product->price }} {{ $product->currency }}</td>
                        <td class="px-4 py-2 @if (! $product->inStock()) text-red-600 @endif">{{ $product->stock }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No products yet — import a CSV above.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
