<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The vector store. `embedding` is a pgvector vector(1536) column (dimension
     * locked to the embedding model — a one-way door). HNSW index for fast,
     * always-tenant-scoped cosine ANN search.
     */
    public function up(): void
    {
        $dimensions = (int) config('ai.embedding_dimensions', 1536);

        Schema::create('kb_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained('knowledge_documents')->cascadeOnDelete();
            $table->text('content');
            $table->jsonb('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('workspace_id');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE kb_chunks ADD COLUMN embedding vector({$dimensions})");
            DB::statement('CREATE INDEX kb_chunks_embedding_hnsw ON kb_chunks USING hnsw (embedding vector_cosine_ops)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_chunks');
    }
};
