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
        // Reseller panels table
        Schema::create('reseller_panels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('panel_name'); // Business name
            $table->string('subdomain')->unique(); // e.g., mybrand.fadsms.com
            $table->string('custom_domain')->unique()->nullable(); // e.g., sms.mybrand.com
            $table->enum('status', ['pending', 'active', 'suspended', 'cancelled'])->default('pending');
            $table->enum('subscription_type', ['monthly', 'annual'])->default('monthly');
            $table->decimal('subscription_fee', 10, 2)->default(30000); // ₦30,000/month
            $table->timestamp('subscription_expires_at')->nullable();
            $table->timestamp('last_payment_at')->nullable();
            $table->boolean('is_paid')->default(false);
            
            // Branding customization
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('primary_color')->default('#FF6B35'); // Orange
            $table->string('secondary_color')->default('#004E89'); // Blue
            $table->string('brand_name')->nullable();
            $table->text('footer_text')->nullable();
            $table->text('terms_url')->nullable();
            $table->text('privacy_url')->nullable();
            
            // Pricing & Margins
            $table->decimal('sms_margin_percentage', 5, 2)->default(10); // 10% markup on SMS
            $table->decimal('vtu_margin_percentage', 5, 2)->default(5); // 5% markup on VTU
            $table->decimal('airtime_margin_percentage', 5, 2)->default(5);
            $table->decimal('data_margin_percentage', 5, 2)->default(5);
            $table->decimal('electricity_margin_percentage', 5, 2)->default(5);
            
            // Payment Gateway Configuration
            $table->enum('payment_gateway', ['paystack', 'payvibe', 'flutterwave'])->default('paystack');
            $table->text('paystack_public_key')->nullable();
            $table->text('paystack_secret_key')->nullable();
            $table->text('paystack_webhook_secret')->nullable();
            $table->text('payvibe_api_key')->nullable();
            $table->text('payvibe_contract_code')->nullable();
            $table->boolean('payment_gateway_enabled')->default(false);
            
            // Features/Permissions (Simplified)
            $table->boolean('can_manage_own_users')->default(true); // Only their users
            $table->boolean('can_view_own_transactions')->default(true); // Only their transactions
            $table->boolean('can_manage_support')->default(true); // Their users' support
            $table->boolean('can_set_pricing')->default(true); // Set their own margins
            $table->boolean('can_view_statistics')->default(true); // Their statistics only
            
            // What to HIDE from reseller admin
            $table->boolean('hide_api_services')->default(true);
            $table->boolean('hide_main_pricing')->default(true);
            $table->boolean('hide_broadcasts')->default(true);
            $table->boolean('hide_all_users')->default(true); // Can't see main platform users
            $table->boolean('hide_provider_balances')->default(true);
            
            // Financials
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->integer('total_users')->default(0);
            $table->integer('total_transactions')->default(0);
            
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('subdomain');
            $table->index('custom_domain');
            $table->index('status');
        });

        // Reseller payments table
        Schema::create('reseller_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_panel_id')->constrained('reseller_panels')->onDelete('cascade');
            $table->string('payment_reference')->unique();
            $table->decimal('amount', 10, 2);
            $table->enum('payment_type', ['subscription', 'setup', 'renewal'])->default('subscription');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index(['reseller_panel_id', 'status']);
        });

        // Reseller users table (users created under reseller panel)
        Schema::create('reseller_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_panel_id')->constrained('reseller_panels')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('added_at');
            $table->timestamps();
            
            $table->unique(['reseller_panel_id', 'user_id']);
            $table->index('reseller_panel_id');
        });

        // Add reseller_id to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'reseller_id')) {
                $table->foreignId('reseller_id')->nullable()->constrained('reseller_panels')->onDelete('set null')->after('referred_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'reseller_id')) {
                $table->dropForeign(['reseller_id']);
                $table->dropColumn('reseller_id');
            }
        });
        
        Schema::dropIfExists('reseller_users');
        Schema::dropIfExists('reseller_payments');
        Schema::dropIfExists('reseller_panels');
    }
};
