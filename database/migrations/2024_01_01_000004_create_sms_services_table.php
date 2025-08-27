<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('provider', ['5sim', 'dassy', 'tiger_sms']);
            $table->text('api_key');
            $table->string('api_url');
            $table->boolean('is_active')->default(true);
            $table->decimal('balance', 10, 2)->default(0.00);
            $table->timestamp('last_balance_check')->nullable();
            $table->json('settings')->nullable();
            $table->integer('priority')->default(1);
            $table->decimal('success_rate', 5, 2)->default(0.00);
            $table->integer('total_orders')->default(0);
            $table->integer('successful_orders')->default(0);
            $table->timestamps();
            
            $table->index(['provider', 'is_active']);
            $table->index(['priority', 'success_rate']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_services');
    }
};
