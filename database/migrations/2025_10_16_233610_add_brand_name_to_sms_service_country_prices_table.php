<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sms_service_country_prices', function (Blueprint $table) {
            $table->string('brand_name', 100)->nullable()->after('provider');
        });

        // Update existing records with brand names from sms_services table
        $this->updateExistingRecords();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_service_country_prices', function (Blueprint $table) {
            $table->dropColumn('brand_name');
        });
    }

    /**
     * Update existing records with brand names from sms_services table
     */
    private function updateExistingRecords(): void
    {
        // Get all providers and their brand names from sms_services table
        $providerBrands = DB::table('sms_services')
            ->select('provider', 'name')
            ->get()
            ->pluck('name', 'provider')
            ->toArray();

        // Update all records in sms_service_country_prices with brand names
        foreach ($providerBrands as $provider => $brandName) {
            DB::table('sms_service_country_prices')
                ->where('provider', $provider)
                ->whereNull('brand_name') // Only update records that don't have brand_name yet
                ->update(['brand_name' => $brandName]);
        }

        // For any remaining records without brand names, use fallback names
        $fallbackNames = [
            'tiger_sms' => 'FADDED GLOBAL',
            '5sim' => 'FADDED SIM',
            'dassy' => 'FADDED USA ONLY',
            'smspool' => 'FADDED GLOBAL 2',
            'textverified' => 'FADDED VERIFIED',
        ];

        foreach ($fallbackNames as $provider => $fallbackName) {
            DB::table('sms_service_country_prices')
                ->where('provider', $provider)
                ->whereNull('brand_name')
                ->update(['brand_name' => $fallbackName]);
        }

        // For any other providers, use a formatted version of the provider name
        DB::table('sms_service_country_prices')
            ->whereNull('brand_name')
            ->update([
                'brand_name' => DB::raw("CONCAT('FADDED ', UPPER(REPLACE(provider, '_', ' ')))")
            ]);
    }
};