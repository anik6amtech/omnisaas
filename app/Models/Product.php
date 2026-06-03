<?php

namespace App\Models;

use App\Domain\Tenancy\Concerns\BelongsToWorkspace;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $workspace_id
 * @property string $name
 * @property string|null $sku
 * @property string|null $description
 * @property string $price
 * @property string $currency
 * @property int $stock
 * @property array<int, mixed>|null $variants
 * @property bool $active
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToWorkspace, HasFactory, HasUuids;

    protected $fillable = [
        'workspace_id', 'name', 'sku', 'description', 'price',
        'currency', 'stock', 'variants', 'images', 'active',
    ];

    protected $attributes = ['currency' => 'BDT', 'stock' => 0, 'active' => true];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'variants' => 'array',
            'images' => 'array',
            'active' => 'boolean',
        ];
    }

    public function inStock(): bool
    {
        return $this->stock > 0;
    }
}
