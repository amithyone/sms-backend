<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sms_service_country_prices', function (Blueprint $table) {
            if (!Schema::hasColumn('sms_service_country_prices', 'provider_currency')) {
                $table->string('provider_currency', 10)->default('RUB')->after('country_code');
            }
            if (!Schema::hasColumn('sms_service_country_prices', 'display_ngn')) {
                $table->decimal('display_ngn', 12, 2)->default(0)->after('cost');
            }
            if (!Schema::hasColumn('sms_service_country_prices', 'fx_rub_usd')) {
                $table->decimal('fx_rub_usd', 12, 6)->nullable()->after('display_ngn');
            }
            if (!Schema::hasColumn('sms_service_country_prices', 'fx_usd_ngn')) {
                $table->decimal('fx_usd_ngn', 12, 6)->nullable()->after('fx_rub_usd');
            }
            if (!Schema::hasColumn('sms_service_country_prices', 'fx_computed_at')) {
                $table->timestamp('fx_computed_at')->nullable()->after('fx_usd_ngn');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sms_service_country_prices', function (Blueprint $table) {
            if (Schema::hasColumn('sms_service_country_prices', 'provider_currency')) {
                $table->dropColumn('provider_currency');
            }
            if (Schema::hasColumn('sms_service_country_prices', 'display_ngn')) {
                $table->dropColumn('display_ngn');
            }
            if (Schema::hasColumn('sms_service_country_prices', 'fx_rub_usd')) {
                $table->dropColumn('fx_rub_usd');
            }
            if (Schema::hasColumn('sms_service_country_prices', 'fx_usd_ngn')) {
                $table->dropColumn('fx_usd_ngn');
            }
            if (Schema::hasColumn('sms_service_country_prices', 'fx_computed_at')) {
                $table->dropColumn('fx_computed_at');
            }
        });
    }
};



