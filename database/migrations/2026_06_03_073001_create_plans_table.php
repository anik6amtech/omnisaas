<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DB-defined plans, editable from the control plane (E9). `entitlements`
     * holds the limits (channels, ai_replies, seats, products) and feature
     * toggles — the app reads these at runtime, never hardcoded constants.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price_bdt', 12, 2)->default(0);
            $table->decimal('price_usd', 12, 2)->default(0);
            $table->string('billing_cycle')->default('monthly'); // monthly | yearly
            $table->unsignedInteger('trial_days')->default(0);
            $table->jsonb('entitlements')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
