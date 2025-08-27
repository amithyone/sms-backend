<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsService extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'provider',
        'api_key',
        'api_url',
        'is_active',
        'balance',
        'last_balance_check',
        'settings',
        'priority',
        'success_rate',
        'total_orders',
        'successful_orders'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'balance' => 'decimal:2',
        'last_balance_check' => 'datetime',
        'settings' => 'array',
        'priority' => 'integer',
        'success_rate' => 'decimal:2',
        'total_orders' => 'integer',
        'successful_orders' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // SMS Providers
    const PROVIDER_5SIM = '5sim';
    const PROVIDER_DASSY = 'dassy';
    const PROVIDER_TIGER_SMS = 'tiger_sms';

    public function orders(): HasMany
    {
        return $this->hasMany(SmsOrder::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'asc')->orderBy('success_rate', 'desc');
    }

    public function isProvider($provider): bool
    {
        return $this->provider === $provider;
    }

    public function updateBalance(float $amount): void
    {
        $this->update(['balance' => $amount, 'last_balance_check' => now()]);
    }

    public function updateSuccessRate(): void
    {
        if ($this->total_orders > 0) {
            $this->success_rate = ($this->successful_orders / $this->total_orders) * 100;
            $this->save();
        }
    }

    public function incrementOrders(bool $successful = false): void
    {
        $this->increment('total_orders');
        if ($successful) {
            $this->increment('successful_orders');
        }
        $this->updateSuccessRate();
    }

    public function getApiConfig(): array
    {
        return [
            'api_key' => $this->api_key,
            'api_url' => $this->api_url,
            'settings' => $this->settings ?? []
        ];
    }
}
