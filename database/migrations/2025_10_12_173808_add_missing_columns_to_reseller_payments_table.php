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
        // First, rename columns that already exist
        if (Schema::hasColumn('reseller_payments', 'status') && !Schema::hasColumn('reseller_payments', 'payment_status')) {
            Schema::table('reseller_payments', function (Blueprint $table) {
                $table->renameColumn('status', 'payment_status');
            });
        }
        
        if (Schema::hasColumn('reseller_payments', 'expires_at') && !Schema::hasColumn('reseller_payments', 'period_end')) {
            Schema::table('reseller_payments', function (Blueprint $table) {
                $table->renameColumn('expires_at', 'period_end');
            });
        }
        
        // Then add new columns
        Schema::table('reseller_payments', function (Blueprint $table) {
            // Add user_id column
            if (!Schema::hasColumn('reseller_payments', 'user_id')) {
                $table->foreignId('user_id')->after('reseller_panel_id')->constrained('users')->onDelete('cascade');
            }
            
            // Add payment_method column
            if (!Schema::hasColumn('reseller_payments', 'payment_method')) {
                $table->string('payment_method')->after('payment_type')->default('wallet');
            }
            
            // Add payment_status if it doesn't exist (in case rename didn't work)
            if (!Schema::hasColumn('reseller_payments', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'completed', 'failed'])->after('payment_method')->default('pending');
            }
            
            // Add period_start column
            if (!Schema::hasColumn('reseller_payments', 'period_start')) {
                $table->timestamp('period_start')->after('paid_at')->nullable();
            }
            
            // Add period_end if it doesn't exist (in case rename didn't work)
            if (!Schema::hasColumn('reseller_payments', 'period_end')) {
                $table->timestamp('period_end')->after('period_start')->nullable();
            }
            
            // Add metadata column
            if (!Schema::hasColumn('reseller_payments', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reseller_payments', function (Blueprint $table) {
            if (Schema::hasColumn('reseller_payments', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('reseller_payments', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
            if (Schema::hasColumn('reseller_payments', 'payment_status')) {
                $table->renameColumn('payment_status', 'status');
            }
            if (Schema::hasColumn('reseller_payments', 'period_end')) {
                $table->renameColumn('period_end', 'expires_at');
            }
            if (Schema::hasColumn('reseller_payments', 'period_start')) {
                $table->dropColumn('period_start');
            }
            if (Schema::hasColumn('reseller_payments', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });
    }
};
