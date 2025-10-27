<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'payment_method',
        'crypto_amount',
        'exchange_rate',
        'naira_amount',
        'user_wallet_address',
        'user_paypal_email',
        'recipient_account_number',
        'recipient_account_name',
        'recipient_bank_name',
        'recipient_phone',
        'proof_of_payment',
        'status',
        'admin_notes',
        'processed_by',
        'processed_at'
    ];

    protected $casts = [
        'crypto_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'naira_amount' => 'decimal:2',
        'proof_of_payment' => 'array',
        'processed_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
