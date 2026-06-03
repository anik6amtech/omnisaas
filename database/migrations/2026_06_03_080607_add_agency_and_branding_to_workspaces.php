<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agency / multi-workspace + white-label: a workspace may have a parent
     * (agency) and its own branding + custom domain (licensed/agency edition).
     */
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->foreignUuid('parent_id')->nullable()->after('id')
                ->constrained('workspaces')->nullOnDelete();
            $table->string('custom_domain')->nullable()->unique();
            $table->jsonb('branding')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['custom_domain', 'branding']);
        });
    }
};
