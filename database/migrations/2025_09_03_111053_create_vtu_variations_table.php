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
        Schema::create('vtu_variations', function (Blueprint $table) {
            $table->id();
            $table->string('service_type'); // airtime, data, cable, etc.
            $table->string('provider'); // vtu_ng, irecharge, etc.
            $table->string('network');
            $table->string('variation_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->string('unit'); // MB, GB, minutes, etc.
            $table->integer('validity_days')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['service_type', 'provider', 'network']);
            $table->index(['variation_code', 'is_active']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vtu_variations');
    }
};
