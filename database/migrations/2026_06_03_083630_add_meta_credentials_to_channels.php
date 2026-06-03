<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-channel Meta app credentials for the "bring your own Meta app" model
     * (self-hosted / agency). app_secret + verify_token are encrypted at rest;
     * all are nullable — when blank the platform falls back to the operator's
     * shared app in config/services.php (hosted model).
     */
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->string('app_id')->nullable()->after('external_id');
            $table->text('app_secret')->nullable()->after('app_id');        // encrypted cast
            $table->text('verify_token')->nullable()->after('app_secret');  // encrypted cast
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn(['app_id', 'app_secret', 'verify_token']);
        });
    }
};
