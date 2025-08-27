<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('sms_service_id');
            $table->string('order_id')->unique();
            $table->string('phone_number');
            $table->string('country', 10);
            $table->string('service');
            $table->decimal('cost', 10, 2);
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled', 'expired', 'failed'])->default('pending');
            $table->string('sms_code')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->json('metadata')->nullable();
            $table->string('provider_order_id')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['sms_service_id', 'status']);
            $table->index(['order_id']);
            $table->index(['phone_number']);
            $table->index(['service', 'country']);
            $table->index(['status', 'created_at']);
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('sms_service_id')->references('id')->on('sms_services')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_orders');
    }
};
