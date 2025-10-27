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
            if (!Schema::hasColumn('reseller_panels', 'wallet_balance')) {
                $table->decimal('wallet_balance', 10, 2)->default(0)->after('subscription_fee');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reseller_panels', function (Blueprint $table) {
            if (Schema::hasColumn('reseller_panels', 'wallet_balance')) {
                $table->dropColumn('wallet_balance');
            }
        });
    }
};
