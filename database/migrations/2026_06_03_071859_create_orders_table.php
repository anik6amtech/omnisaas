<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Orders / leads captured from conversations. Money fields are stored;
     * `payment_status` tracks the buyer payment (separate from `status`, the
     * fulfilment pipeline).
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('new'); // new|confirmed|shipped|delivered|cancelled|returned
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('delivery_zone')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('delivery_charge', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->string('payment_status')->default('unpaid'); // unpaid|paid|refunded
            $table->jsonb('meta')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
