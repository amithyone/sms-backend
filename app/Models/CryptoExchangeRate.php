<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_method',
        'rate_per_usd',
        'is_enabled',
        'instructions',
        'disclaimer',
        'admin_wallet_address',
        'admin_paypal_email',
        'min_amount',
        'max_amount',
        'updated_by'
    ];

    protected $casts = [
        'rate_per_usd' => 'decimal:2',
        'is_enabled' => 'boolean',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2'
    ];

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }
}
