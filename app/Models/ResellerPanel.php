<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResellerPanel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'panel_name',
        'subdomain',
        'custom_domain',
        'status',
        'subscription_type',
        'subscription_fee',
        'wallet_balance',
        'subscription_expires_at',
        'last_payment_at',
        'is_paid',
        'logo_url',
        'favicon_url',
        'primary_color',
        'secondary_color',
        'brand_name',
        'footer_text',
        'terms_url',
        'privacy_url',
        'sms_margin_percentage',
        'vtu_margin_percentage',
        'airtime_margin_percentage',
        'data_margin_percentage',
        'electricity_margin_percentage',
        'payment_gateway',
        'paystack_public_key',
        'paystack_secret_key',
        'paystack_webhook_secret',
        'flutterwave_public_key',
        'flutterwave_secret_key',
        'flutterwave_encryption_key',
        'payvibe_api_key',
        'payvibe_contract_code',
        'payment_gateway_enabled',
        'can_manage_own_users',
        'can_view_own_transactions',
        'can_manage_support',
        'can_set_pricing',
        'can_view_statistics',
        'hide_api_services',
        'hide_main_pricing',
        'hide_broadcasts',
        'hide_all_users',
        'hide_provider_balances',
        'total_revenue',
        'total_users',
        'total_transactions'
    ];

    protected $casts = [
        'subscription_expires_at' => 'datetime',
        'last_payment_at' => 'datetime',
        'is_paid' => 'boolean',
        'subscription_fee' => 'decimal:2',
        'wallet_balance' => 'decimal:2',
        'sms_margin_percentage' => 'decimal:2',
        'vtu_margin_percentage' => 'decimal:2',
        'airtime_margin_percentage' => 'decimal:2',
        'data_margin_percentage' => 'decimal:2',
        'electricity_margin_percentage' => 'decimal:2',
        'payment_gateway_enabled' => 'boolean',
        'can_manage_own_users' => 'boolean',
        'can_view_own_transactions' => 'boolean',
        'can_manage_support' => 'boolean',
        'can_set_pricing' => 'boolean',
        'can_view_statistics' => 'boolean',
        'hide_api_services' => 'boolean',
        'hide_main_pricing' => 'boolean',
        'hide_broadcasts' => 'boolean',
        'hide_all_users' => 'boolean',
        'hide_provider_balances' => 'boolean',
        'total_revenue' => 'decimal:2',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ResellerPayment::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'reseller_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_paid', true);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function isExpired(): bool
    {
        return $this->subscription_expires_at && $this->subscription_expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->is_paid && !$this->isExpired();
    }

    /**
     * Update reseller wallet balance
     */
    public function updateWalletBalance(float $amount, string $action = 'add'): bool
    {
        if ($action === 'add') {
            $this->wallet_balance += $amount;
        } elseif ($action === 'subtract') {
            if ($this->wallet_balance < $amount) {
                return false; // Insufficient balance
            }
            $this->wallet_balance -= $amount;
        }
        
        return $this->save();
    }

    /**
     * Check if reseller can afford a purchase
     */
    public function canAfford(float $amount): bool
    {
        return $this->wallet_balance >= $amount;
    }
}
