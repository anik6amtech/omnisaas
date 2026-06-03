<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string $price_bdt
 * @property int $trial_days
 * @property array<string, mixed>|null $entitlements
 * @property bool $active
 */
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'name', 'slug', 'price_bdt', 'price_usd', 'billing_cycle',
        'trial_days', 'entitlements', 'active', 'sort',
    ];

    protected $attributes = ['billing_cycle' => 'monthly', 'trial_days' => 0, 'active' => true];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_bdt' => 'decimal:2',
            'price_usd' => 'decimal:2',
            'trial_days' => 'integer',
            'entitlements' => 'array',
            'active' => 'boolean',
        ];
    }

    /** A numeric limit from the plan (null = unlimited). */
    public function limit(string $key): ?int
    {
        $value = Arr::get($this->entitlements ?? [], "limits.{$key}");

        return $value === null ? null : (int) $value;
    }

    public function allowsFeature(string $feature): bool
    {
        return in_array($feature, Arr::get($this->entitlements ?? [], 'features', []), true);
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
