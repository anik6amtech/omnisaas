<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-workspace usage metering — AI tokens, embeddings, template fees. ULID
     * PK (hot, ever-growing). Rolled up into billing/analytics by the scheduler.
     */
    public function up(): void
    {
        Schema::create('usage_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // ai_reply | embedding | wa_template | inbound
            $table->unsignedInteger('tokens')->default(0);
            $table->decimal('cost', 12, 6)->default(0);
            $table->string('model')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['workspace_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_events');
    }
};
