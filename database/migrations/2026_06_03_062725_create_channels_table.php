<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A connected Meta channel (WhatsApp / Instagram / Facebook) for a workspace.
     * `access_token` is encrypted at rest (model cast). Inbound webhooks resolve
     * the channel by (type, external_id).
     */
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('type');                       // whatsapp | instagram | facebook
            $table->string('external_id');                // Meta page / IG / WABA phone-number id
            $table->string('name')->nullable();
            $table->text('access_token')->nullable();     // encrypted cast
            $table->timestamp('token_expires_at')->nullable();
            $table->string('status')->default('active');  // active | disconnected | error
            $table->jsonb('settings')->nullable();
            $table->jsonb('meta')->nullable();            // raw provider metadata
            $table->timestamps();

            $table->unique(['type', 'external_id']);
            $table->index(['workspace_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
