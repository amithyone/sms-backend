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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('type', [
                'credit',
                'debit', 
                'transfer',
                'withdrawal',
                'deposit',
                'service_purchase',
                'refund',
                'commission'
            ]);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->text('description');
            $table->string('reference')->unique();
            $table->enum('status', ['pending', 'success', 'failed', 'cancelled'])->default('pending');
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->decimal('fee', 10, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->timestamps();

            // Indexes for better performance
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'status']);
            $table->index(['reference']);
            $table->index(['created_at']);
            $table->index(['service_id']);

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
