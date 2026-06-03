<?php

namespace App\Models;

use App\Domain\Tenancy\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * A gateway card-token reference (the card lives at SSLCommerz — minimal PCI).
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $token
 * @property bool $active
 */
class PaymentToken extends Model
{
    use BelongsToWorkspace, HasUuids;

    protected $fillable = ['workspace_id', 'gateway', 'token', 'brand', 'last_four', 'active'];

    protected $hidden = ['token'];

    protected $attributes = ['gateway' => 'sslcommerz', 'active' => true];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
