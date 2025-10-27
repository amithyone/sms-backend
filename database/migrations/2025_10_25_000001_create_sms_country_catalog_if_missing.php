<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sms_country_catalog')) {
            Schema::create('sms_country_catalog', function (Blueprint $table) {
                $table->id();
                $table->string('provider');
                $table->string('country_code');
                $table->string('country_name');
                $table->timestamps();
                $table->unique(['provider', 'country_code']);
            });
        }
    }

    public function down(): void
    {
        // Do not drop if other components depend on it
        // Schema::dropIfExists('sms_country_catalog');
    }
};


