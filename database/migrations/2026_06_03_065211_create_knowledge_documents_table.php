<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A seller's knowledge source (FAQ / policy / URL / catalog text). Chunked
     * and embedded into kb_chunks for tenant-scoped RAG retrieval.
     */
    public function up(): void
    {
        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('source_type')->default('faq'); // faq | policy | url | catalog
            $table->string('title')->nullable();
            $table->text('content');
            $table->string('status')->default('pending'); // pending | indexed | failed
            $table->timestamp('indexed_at')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'source_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_documents');
    }
};
