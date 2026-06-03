<?php

namespace App\Models;

use App\Domain\Tenancy\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property string $id
 * @property string $workspace_id
 * @property string $subscription_id
 * @property string $number
 * @property string $amount
 * @property string $status
 */
class Invoice extends Model
{
    use BelongsToWorkspace, HasUuids;

    protected $fillable = [
        'workspace_id', 'subscription_id', 'number', 'amount', 'currency', 'status', 'due_at', 'paid_at',
    ];

    protected $attributes = ['status' => 'pending', 'currency' => 'BDT'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'due_at' => 'datetime', 'paid_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
