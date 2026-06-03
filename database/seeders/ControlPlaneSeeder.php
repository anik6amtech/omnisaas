<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the control plane (/admin): operator RBAC roles (admin guard) and a
 * default Super Admin operator. Idempotent — safe to re-run.
 *
 * Default login: admin@omnireply.test / password  (CHANGE in any real env).
 */
class ControlPlaneSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Operator roles per the dev plan (§8.5). Permissions are attached as
        // the control-plane features land (E9).
        foreach (['Super Admin', 'Support', 'Billing', 'Content Editor'] as $roleName) {
            Role::findOrCreate($roleName, 'admin');
        }

        $operator = AdminUser::query()->updateOrCreate(
            ['email' => 'admin@omnireply.test'],
            [
                'name' => 'OmniReply Operator',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $operator->syncRoles(['Super Admin']);
    }
}
