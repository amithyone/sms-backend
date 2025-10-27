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
        Schema::create('cached_sms_services', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->index(); // tiger_sms, 5sim, dassy, etc.
            $table->string('provider_name', 100); // Display name from SMS services table
            $table->string('country_code', 10)->index(); // US, UK, etc.
            $table->string('country_name', 100); // United States, United Kingdom, etc.
            $table->string('service_code', 100)->index(); // whatsapp, telegram, etc.
            $table->string('service_name', 200); // WhatsApp, Telegram, etc.
            $table->decimal('cost_ngn', 10, 2); // Final cost in NGN (after conversion and minimum rules)
            $table->decimal('original_cost', 10, 2); // Original cost from provider
            $table->string('original_currency', 10)->default('USD'); // Original currency
            $table->integer('available_count')->default(0); // Number available
            $table->boolean('is_popular')->default(false); // Popular service flag
            $table->string('status', 20)->default('active'); // active, inactive
            $table->json('metadata')->nullable(); // Additional provider-specific data
            $table->timestamp('last_updated'); // When this record was last fetched
            $table->timestamps();

            // Indexes for fast queries
            $table->index(['provider', 'country_code']);
            $table->index(['country_code', 'service_code']);
            $table->index(['provider', 'service_code']);
            $table->index(['last_updated']);
            
            // Unique constraint to prevent duplicates
            $table->unique(['provider', 'country_code', 'service_code'], 'unique_provider_country_service');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cached_sms_services');
    }
};