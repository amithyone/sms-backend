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
        // Referral relationships table
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referred_id')->constrained('users')->onDelete('cascade');
            $table->string('referral_code', 50)->unique();
            $table->timestamp('referred_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['referrer_id', 'referred_id']);
            $table->index('referral_code');
        });

        // Referral commissions table
        Schema::create('referral_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_id')->constrained('referrals')->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->decimal('amount', 15, 2); // Commission amount
            $table->decimal('transaction_amount', 15, 2); // Original transaction amount
            $table->decimal('commission_rate', 5, 4); // Commission rate (e.g., 0.05 for 5%)
            $table->enum('type', ['first_deposit', 'recurring_deposit', 'bonus']);
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->text('description')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->index(['referral_id', 'status']);
            $table->index('type');
        });

        // Referral statistics table
        Schema::create('referral_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->integer('total_referrals')->default(0);
            $table->integer('active_referrals')->default(0);
            $table->decimal('total_commission_earned', 15, 2)->default(0);
            $table->decimal('pending_commission', 15, 2)->default(0);
            $table->decimal('paid_commission', 15, 2)->default(0);
            $table->decimal('total_volume', 15, 2)->default(0); // Total volume from referrals
            $table->enum('tier', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze');
            $table->decimal('tier_rate', 5, 4)->default(0.01); // Current tier commission rate
            $table->timestamp('last_updated_at');
            $table->timestamps();
            
            $table->unique('referrer_id');
            $table->index('tier');
        });

        // Add foreign key constraint for referred_by if it doesn't exist
        Schema::table('users', function (Blueprint $table) {
            // Check if foreign key doesn't exist before adding
            if (!Schema::hasColumn('users', 'referred_by_foreign')) {
                $table->foreign('referred_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_stats');
        Schema::dropIfExists('referral_commissions');
        Schema::dropIfExists('referrals');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
        });
    }
};
