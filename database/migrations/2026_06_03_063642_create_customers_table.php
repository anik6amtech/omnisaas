<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A buyer, unified across channels. `channel_identities` maps a channel type
     * to the platform id (wa_id / psid / igsid) so the same person on WhatsApp
     * and Messenger can be merged into one profile.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->jsonb('channel_identities')->nullable();
            $table->boolean('opted_out')->default(false);
            $table->jsonb('data')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'phone']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX customers_channel_identities_gin ON customers USING gin (channel_identities)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
