<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reseller_panels', function (Blueprint $table) {
            if (!Schema::hasColumn('reseller_panels', 'flutterwave_public_key')) {
                $table->string('flutterwave_public_key')->nullable()->after('paystack_webhook_secret');
            }
            if (!Schema::hasColumn('reseller_panels', 'flutterwave_secret_key')) {
                $table->string('flutterwave_secret_key')->nullable()->after('flutterwave_public_key');
            }
            if (!Schema::hasColumn('reseller_panels', 'flutterwave_encryption_key')) {
                $table->string('flutterwave_encryption_key')->nullable()->after('flutterwave_secret_key');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reseller_panels', function (Blueprint $table) {
            $table->dropColumn([
                'flutterwave_public_key',
                'flutterwave_secret_key',
                'flutterwave_encryption_key'
            ]);
        });
    }
};
