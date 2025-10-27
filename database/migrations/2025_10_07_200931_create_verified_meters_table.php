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
        Schema::create('verified_meters', function (Blueprint $table) {
            $table->id();
            $table->string('service_id')->index(); // e.g., 'ikeja-electric', 'eko-electric'
            $table->string('meter_number')->index(); // customer_id/meter number
            $table->string('meter_type'); // 'prepaid' or 'postpaid'
            $table->string('customer_name')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('account_type')->nullable();
            $table->decimal('outstanding_balance', 10, 2)->nullable();
            $table->json('verification_data')->nullable(); // Store full API response
            $table->timestamp('last_verified_at');
            $table->timestamp('expires_at')->nullable(); // Cache expiration
            $table->timestamps();
            
            // Unique constraint - one entry per meter
            $table->unique(['service_id', 'meter_number', 'meter_type'], 'unique_meter');
            
            // Index for cleanup queries
            $table->index('last_verified_at');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verified_meters');
    }
};
