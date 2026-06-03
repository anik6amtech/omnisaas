<?php

namespace App\Models;

use App\Domain\Tenancy\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A gateway payment (buyer order or tenant subscription). `tran_id` unique →
 * IPN idempotency.
 *
 * @property string $id
 * @property string|null $workspace_id
 * @property string $tran_id
 * @property string|null $val_id
 * @property string $amount
 * @property string $status
 */
class Payment extends Model
{
    use BelongsToWorkspace, HasUlids;

    protected $fillable = [
        'workspace_id', 'payable_type', 'payable_id', 'gateway',
        'tran_id', 'val_id', 'amount', 'currency', 'status', 'raw', 'paid_at',
    ];

    protected $attributes = ['gateway' => 'sslcommerz', 'status' => 'pending', 'currency' => 'BDT'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'raw' => 'array', 'paid_at' => 'datetime'];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
