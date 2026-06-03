<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stored card-token REFERENCE only (the card itself lives at SSLCommerz) →
     * minimal PCI scope. Used by the scheduler for tokenized auto-rebill.
     */
    public function up(): void
    {
        Schema::create('payment_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('gateway')->default('sslcommerz');
            $table->string('token'); // reference token from the gateway
            $table->string('brand')->nullable();
            $table->string('last_four', 4)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_tokens');
    }
};
