<?php

namespace App\Models;

use App\Domain\Tenancy\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $workspace_id
 * @property string $type
 * @property int $tokens
 * @property string $cost
 * @property string|null $model
 */
class UsageEvent extends Model
{
    use BelongsToWorkspace, HasUlids;

    const UPDATED_AT = null;

    protected $fillable = ['workspace_id', 'type', 'tokens', 'cost', 'model', 'meta'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['tokens' => 'integer', 'cost' => 'decimal:6', 'meta' => 'array'];
    }
}
