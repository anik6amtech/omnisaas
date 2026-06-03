<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant webhook verify token. Each workspace gets its own stable token
 * (lazily generated) that the seller pastes into their Meta app's webhook
 * config — replacing reliance on a single shared .env value, so a tenant on
 * their own Meta app verifies against a token unique to them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->string('webhook_verify_token')->nullable()->after('branding');
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn('webhook_verify_token');
        });
    }
};
