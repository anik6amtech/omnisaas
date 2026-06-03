<?php

namespace App\Models;

use Database\Factories\AdminUserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * An operator (the OmniReply team) — the control plane (/admin) identity on the
 * dedicated `admin` guard. RBAC via spatie/laravel-permission, scoped to the
 * `admin` guard. Kept separate from tenant {@see User}s by design.
 */
#[Fillable(['name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class AdminUser extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<AdminUserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /** spatie/laravel-permission: scope this model's roles to the admin guard. */
    protected string $guard_name = 'admin';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /** Only active operators may reach the Filament control panel. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }
}
