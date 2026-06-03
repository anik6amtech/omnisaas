<?php

namespace App\Models;

use App\Domain\Tenancy\Concerns\BelongsToWorkspace;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property string $id
 * @property string $workspace_id
 * @property string $reference
 * @property string $status
 * @property string $payment_status
 * @property string $total
 * @property string $currency
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use BelongsToWorkspace, HasFactory, HasUuids;

    protected $fillable = [
        'workspace_id', 'customer_id', 'conversation_id', 'reference', 'status',
        'customer_name', 'customer_phone', 'address', 'delivery_zone',
        'subtotal', 'delivery_charge', 'total', 'currency', 'payment_status', 'meta',
    ];

    protected $attributes = ['status' => 'new', 'payment_status' => 'unpaid', 'currency' => 'BDT'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'delivery_charge' => 'decimal:2',
            'total' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
