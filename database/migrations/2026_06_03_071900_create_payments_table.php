<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Polymorphic gateway payments — buyer order payments (E7) and tenant
     * subscription/invoice payments (E8) both flow through here. `tran_id` is
     * unique → idempotent IPN handling; the IPN (validated by `val_id`) is the
     * source of truth, never the browser return URL.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUuid('workspace_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuidMorphs('payable'); // Order | Invoice | Subscription
            $table->string('gateway')->default('sslcommerz');
            $table->string('tran_id')->unique();
            $table->string('val_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('BDT');
            $table->string('status')->default('pending'); // pending|paid|failed|cancelled
            $table->jsonb('raw')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
