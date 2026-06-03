<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the tenant plane (/app): one demo workspace and an owner seller who
 * authenticates on the web guard. Idempotent — safe to re-run.
 *
 * Default login: seller@omnireply.test / password  (CHANGE in any real env).
 */
class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::query()->updateOrCreate(
            ['slug' => 'demo-store'],
            [
                'name' => 'Demo Store',
                'plan' => 'free',
                'locale' => 'en',
                'timezone' => 'Asia/Dhaka',
                'status' => 'active',
            ],
        );

        $seller = User::query()->updateOrCreate(
            ['email' => 'seller@omnireply.test'],
            [
                'name' => 'Demo Seller',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'current_workspace_id' => $workspace->getKey(),
            ],
        );

        // Owner membership (tenant-level role).
        $workspace->users()->syncWithoutDetaching([
            $seller->getKey() => ['role' => 'owner'],
        ]);
    }
}
