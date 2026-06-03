<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A tenant user (seller/agent) can belong to several workspaces (agency
     * persona). `current_workspace_id` tracks the one they're actively viewing;
     * the BelongsToWorkspace global scope binds to it for the web guard.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('current_workspace_id')
                ->nullable()
                ->after('remember_token')
                ->constrained('workspaces')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_workspace_id');
        });
    }
};
