<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('sms_service_country_prices')) {
            Schema::create('sms_service_country_prices', function (Blueprint $table) {
                $table->id();
                $table->string('provider');
                $table->string('service');
                $table->string('service_name')->nullable();
                $table->string('country_code');
                $table->decimal('cost', 12, 2)->default(0);
                $table->unsignedInteger('count')->default(0);
                $table->timestamp('last_seen_at')->nullable();
                // pricing + fx
                $table->string('provider_currency', 10)->default('RUB');
                $table->decimal('display_ngn', 12, 2)->default(0);
                $table->decimal('fx_rub_usd', 12, 6)->nullable();
                $table->decimal('fx_usd_ngn', 12, 6)->nullable();
                $table->timestamp('fx_computed_at')->nullable();
                $table->timestamps();
                $table->unique(['provider','service','country_code']);
                $table->index(['provider','country_code']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_service_country_prices');
    }
};



