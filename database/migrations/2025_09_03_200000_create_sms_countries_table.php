<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_countries', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // e.g., tiger_sms
            $table->string('country_id'); // provider-specific country id (string)
            $table->string('name'); // human-readable name
            $table->timestamps();
            $table->unique(['provider', 'country_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_countries');
    }
};


