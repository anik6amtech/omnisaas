<?php

namespace App\Domain\Catalog\Actions;

use App\Models\Product;
use Illuminate\Support\Arr;

/**
 * Imports/updates products from CSV (headers: name, sku, price, stock,
 * description, currency). Idempotent on sku within the active workspace. Run
 * with the workspace context set — the global scope handles isolation and fills
 * workspace_id.
 */
class ImportCatalogCsv
{
    /** @return int the number of rows imported */
    public function execute(string $csv): int
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($csv)) ?: [];

        if (count($lines) < 2) {
            return 0;
        }

        $header = array_map(
            static fn (string $h): string => strtolower(trim($h)),
            str_getcsv((string) array_shift($lines)),
        );

        $count = 0;

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $row = array_combine($header, array_pad(str_getcsv($line), count($header), null));

            $attributes = [
                'name' => (string) Arr::get($row, 'name'),
                'description' => Arr::get($row, 'description'),
                'price' => (float) Arr::get($row, 'price', 0),
                'stock' => (int) Arr::get($row, 'stock', 0),
                'currency' => Arr::get($row, 'currency', 'BDT'),
            ];

            $sku = Arr::get($row, 'sku');

            if ($sku !== null && $sku !== '') {
                Product::query()->updateOrCreate(['sku' => $sku], $attributes);
            } else {
                Product::query()->create($attributes);
            }

            $count++;
        }

        return $count;
    }
}
