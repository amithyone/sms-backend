<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'total_referrals',
        'active_referrals',
        'total_commission_earned',
        'pending_commission',
        'paid_commission',
        'total_volume',
        'tier',
        'tier_rate',
        'last_updated_at'
    ];

    protected $casts = [
        'total_commission_earned' => 'decimal:2',
        'pending_commission' => 'decimal:2',
        'paid_commission' => 'decimal:2',
        'total_volume' => 'decimal:2',
        'tier_rate' => 'decimal:4',
        'last_updated_at' => 'datetime'
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    // Tier definitions
    public static function getTierRate($referralCount): float
    {
        if ($referralCount >= 100) return 0.015; // 1.5% for 100+ referrals
        if ($referralCount >= 50) return 0.012;  // 1.2% for 50+ referrals  
        if ($referralCount >= 20) return 0.011;  // 1.1% for 20+ referrals
        return 0.01; // 1% default
    }

    public static function getTierName($referralCount): string
    {
        if ($referralCount >= 100) return 'platinum';
        if ($referralCount >= 50) return 'gold';
        if ($referralCount >= 20) return 'silver';
        return 'bronze';
    }
}
