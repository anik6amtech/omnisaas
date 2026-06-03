<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A customer conversation on a channel, with its 24-hour service window
     * (`window_expires_at`), status, the AI on/off toggle, and assignment.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('ai_handling'); // ai_handling | needs_human | resolved
            $table->timestamp('window_expires_at')->nullable();
            $table->boolean('ai_enabled')->default(true);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->jsonb('data')->nullable();
            $table->timestamps();

            $table->unique(['channel_id', 'customer_id']);
            $table->index(['workspace_id', 'status', 'window_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
