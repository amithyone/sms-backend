<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vtu_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('category'); // airtime|data|betting|electricity|tv|epins
            $table->string('service_id');
            $table->string('customer_id')->nullable();
            $table->string('variation_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('reference')->unique();
            $table->enum('status', ['initiated', 'processing', 'completed', 'failed', 'refunded'])->default('initiated');
            $table->json('provider_response')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'category']);
            $table->index(['reference']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vtu_orders');
    }
};
