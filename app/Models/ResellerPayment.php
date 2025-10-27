<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_panel_id',
        'user_id',
        'amount',
        'payment_type',
        'payment_method',
        'payment_reference',
        'payment_status',
        'paid_at',
        'period_start',
        'period_end',
        'metadata'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'metadata' => 'array',
        'amount' => 'decimal:2'
    ];

    public function resellerPanel()
    {
        return $this->belongsTo(ResellerPanel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
