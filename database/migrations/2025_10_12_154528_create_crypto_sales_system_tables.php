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
        // Crypto sale requests table
        Schema::create('crypto_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('transaction_id')->unique();
            $table->enum('payment_method', ['usdt', 'paypal', 'bitcoin', 'ethereum'])->default('usdt');
            $table->decimal('crypto_amount', 15, 2); // Amount in USD/crypto
            $table->decimal('exchange_rate', 15, 2); // Rate at time of transaction
            $table->decimal('naira_amount', 15, 2); // Amount to be paid in Naira
            $table->string('user_wallet_address')->nullable(); // Crypto wallet address
            $table->string('user_paypal_email')->nullable(); // PayPal email
            $table->string('recipient_account_number'); // Bank account to receive Naira
            $table->string('recipient_account_name'); // Account name
            $table->string('recipient_bank_name'); // Bank name
            $table->string('recipient_phone'); // Phone number
            $table->text('proof_of_payment')->nullable(); // Screenshot path(s) - JSON array
            $table->enum('status', ['pending', 'processing', 'completed', 'rejected', 'cancelled'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('transaction_id');
        });

        // Crypto exchange rates table
        Schema::create('crypto_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->enum('payment_method', ['usdt', 'paypal', 'bitcoin', 'ethereum'])->unique();
            $table->decimal('rate_per_usd', 15, 2); // Naira per 1 USD
            $table->boolean('is_enabled')->default(true);
            $table->text('instructions')->nullable(); // Payment instructions
            $table->text('disclaimer')->nullable(); // Warning/disclaimer text
            $table->string('admin_wallet_address')->nullable(); // Admin's crypto wallet
            $table->string('admin_paypal_email')->nullable(); // Admin's PayPal
            $table->decimal('min_amount', 15, 2)->default(10); // Minimum USD to sell
            $table->decimal('max_amount', 15, 2)->default(10000); // Maximum USD to sell
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('payment_method');
            $table->index('is_enabled');
        });

        // Add crypto sale fields to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'crypto_account_number')) {
                $table->string('crypto_account_number')->nullable()->after('account_name');
                $table->string('crypto_account_name')->nullable()->after('crypto_account_number');
                $table->string('crypto_bank_name')->nullable()->after('crypto_account_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_sales');
        Schema::dropIfExists('crypto_exchange_rates');
        
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'crypto_account_number')) {
                $table->dropColumn(['crypto_account_number', 'crypto_account_name', 'crypto_bank_name']);
            }
        });
    }
};
