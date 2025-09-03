<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_service_codes', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // e.g., tiger_sms
            $table->string('code'); // provider-specific service code, e.g., 'wa'
            $table->string('name'); // friendly name, e.g., 'WhatsApp'
            $table->timestamps();
            $table->unique(['provider', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_service_codes');
    }
};


