<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalog products. Price/stock are the SOURCE OF TRUTH read relationally by
     * the AI (never vectorized) so a reply can't quote a stale number. `sku` is
     * unique per workspace for idempotent CSV imports.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->integer('stock')->default(0);
            $table->jsonb('variants')->nullable();
            $table->jsonb('images')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['workspace_id', 'sku']);
            $table->index(['workspace_id', 'active']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement('CREATE INDEX products_name_trgm ON products USING gin (name gin_trgm_ops)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
