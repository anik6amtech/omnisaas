<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Workspaces = tenants. The root of the multi-tenant shared-DB model;
     * every tenant-scoped table carries a `workspace_id` FK back to here.
     *
     * TODO (defense-in-depth): once tenant tables exist, enable Postgres
     * Row-Level Security per tenant table with a `tenant_isolation` policy on
     * `current_setting('app.current_workspace')` and set that GUC per
     * request/job — so the DB enforces isolation even if a query forgets the
     * global scope. See architecture doc §7.
     */
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('plan')->default('free');      // resolved against DB-defined plan entitlements
            $table->string('locale', 8)->default('en');
            $table->string('timezone')->default('Asia/Dhaka'); // Bangladesh-first
            $table->string('status')->default('active');  // active|suspended|trialing
            $table->jsonb('data')->nullable();            // flexible per-workspace settings
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
