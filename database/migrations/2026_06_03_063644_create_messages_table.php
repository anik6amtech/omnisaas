<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Canonical in/out messages. ULID PK for index locality on this hot,
     * ever-growing table. `external_id` (Meta message id) is unique → natural
     * idempotency on webhook retries.
     *
     * TODO (scale): range-partition by `created_at` (monthly) once volume grows.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('direction');                    // in | out
            $table->string('author');                       // customer | ai | agent
            $table->text('body')->nullable();
            $table->string('lang')->nullable();
            $table->string('external_id')->nullable()->unique(); // Meta message id (idempotency)
            $table->jsonb('attachments')->nullable();
            $table->string('status')->default('received');  // received | queued | sent | delivered | failed
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->jsonb('meta')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['workspace_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
