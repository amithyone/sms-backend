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
        Schema::create('vtu_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('provider', ['vtu_ng', 'irecharge', 'paystack', 'flutterwave']);
            $table->text('api_key')->nullable(); // For services that use API keys
            $table->string('username')->nullable(); // For services that use username/password
            $table->string('password')->nullable(); // For services that use username/password
            $table->string('pin')->nullable(); // For services that use PIN
            $table->string('api_url');
            $table->boolean('is_active')->default(true);
            $table->decimal('balance', 10, 2)->default(0.00);
            $table->timestamp('last_balance_check')->nullable();
            $table->json('settings')->nullable(); // Store additional configuration
            $table->integer('priority')->default(1); // Priority for fallback order
            $table->decimal('success_rate', 5, 2)->default(0.00);
            $table->integer('total_orders')->default(0);
            $table->integer('successful_orders')->default(0);
            $table->timestamps();
            
            $table->index(['provider', 'is_active']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vtu_services');
    }
};
