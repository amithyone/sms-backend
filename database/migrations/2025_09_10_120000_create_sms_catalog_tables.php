<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_service_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // tiger_sms, 5sim, textverified
            $table->string('service');  // provider-specific key or serviceName
            $table->string('name');     // human readable
            $table->string('description')->nullable();
            $table->string('capability')->nullable(); // sms | voice | smsAndVoiceCombo (TextVerified)
            $table->string('currency', 10)->default('NGN');
            $table->decimal('min_cost', 12, 2)->default(0);
            $table->decimal('max_cost', 12, 2)->default(0);
            $table->decimal('avg_cost', 12, 2)->default(0);
            $table->unsignedBigInteger('samples')->default(0);
            $table->timestamps();
            $table->unique(['provider', 'service']);
            $table->index(['provider', 'name']);
        });

        Schema::create('sms_country_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('country_code');
            $table->string('country_name');
            $table->timestamps();
            $table->unique(['provider', 'country_code']);
        });

        Schema::create('sms_service_country_prices', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('service');
            $table->string('country_code');
            $table->decimal('cost', 12, 2)->default(0);
            $table->unsignedInteger('count')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'service', 'country_code']);
            $table->index(['provider', 'country_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_service_country_prices');
        Schema::dropIfExists('sms_country_catalog');
        Schema::dropIfExists('sms_service_catalog');
    }
};
