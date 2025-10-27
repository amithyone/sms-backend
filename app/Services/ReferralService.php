<?php

namespace App\Services;

use App\Models\User;
use App\Models\Referral;
use App\Models\ReferralCommission;
use App\Models\ReferralStat;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReferralService
{
    /**
     * Generate a unique referral code for a user
     */
    public function generateReferralCode(User $user): string
    {
        // Use username or email prefix + random string
        $prefix = strtoupper(substr($user->username ?: $user->name, 0, 3));
        $random = strtoupper(Str::random(4));
        $code = $prefix . $random;

        // Ensure uniqueness
        while (User::where('referral_code', $code)->exists()) {
            $random = strtoupper(Str::random(4));
            $code = $prefix . $random;
        }

        return $code;
    }

    /**
     * Create referral relationship when user registers with referral code
     */
    public function createReferral(User $referrer, User $referredUser, string $referralCode): ?Referral
    {
        try {
            DB::beginTransaction();

            // Create referral relationship
            $referral = Referral::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $referredUser->id,
                'referral_code' => $referralCode,
                'referred_at' => now(),
                'is_active' => true
            ]);

            // Update referrer's statistics
            $this->updateReferrerStats($referrer->id);

            // Create initial referral stats for referrer if not exists
            $this->initializeReferralStats($referrer->id);

            DB::commit();
            
            Log::info("Referral created successfully", [
                'referrer_id' => $referrer->id,
                'referred_id' => $referredUser->id,
                'referral_code' => $referralCode
            ]);

            return $referral;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create referral", [
                'referrer_id' => $referrer->id,
                'referred_id' => $referredUser->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Process commission when referred user makes a deposit
     */
    public function processDepositCommission(Transaction $transaction): void
    {
        if ($transaction->type !== 'deposit' || $transaction->status !== 'completed') {
            return;
        }

        $user = $transaction->user;
        $referral = Referral::where('referred_id', $user->id)->where('is_active', true)->first();

        if (!$referral) {
            return;
        }

        try {
            DB::beginTransaction();

            $referrerStats = ReferralStat::where('referrer_id', $referral->referrer_id)->first();
            if (!$referrerStats) {
                $this->initializeReferralStats($referral->referrer_id);
                $referrerStats = ReferralStat::where('referrer_id', $referral->referrer_id)->first();
            }

            // Check if this is the first deposit
            $isFirstDeposit = !ReferralCommission::where('referral_id', $referral->id)
                ->where('type', 'first_deposit')
                ->where('status', '!=', 'cancelled')
                ->exists();

            // Calculate commission
            $commissionRate = $isFirstDeposit ? 0.05 : $referrerStats->tier_rate; // 5% for first, tier rate for recurring
            $commissionAmount = $transaction->amount * $commissionRate;

            // Create commission record
            ReferralCommission::create([
                'referral_id' => $referral->id,
                'transaction_id' => $transaction->id,
                'amount' => $commissionAmount,
                'transaction_amount' => $transaction->amount,
                'commission_rate' => $commissionRate,
                'type' => $isFirstDeposit ? 'first_deposit' : 'recurring_deposit',
                'status' => 'pending',
                'description' => $isFirstDeposit 
                    ? "5% commission from {$user->name}'s first deposit"
                    : ($referrerStats->tier_rate * 100) . "% commission from {$user->name}'s deposit"
            ]);

            // Update referrer stats
            $referrerStats->increment('pending_commission', $commissionAmount);
            $referrerStats->increment('total_volume', $transaction->amount);

            // Check and update tier
            $this->updateReferrerTier($referral->referrer_id);

            DB::commit();

            Log::info("Referral commission processed", [
                'referral_id' => $referral->id,
                'transaction_id' => $transaction->id,
                'commission_amount' => $commissionAmount,
                'is_first_deposit' => $isFirstDeposit
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to process referral commission", [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update referrer's statistics
     */
    public function updateReferrerStats(int $referrerId): void
    {
        $referrerStats = ReferralStat::where('referrer_id', $referrerId)->first();
        
        if (!$referrerStats) {
            $this->initializeReferralStats($referrerId);
            $referrerStats = ReferralStat::where('referrer_id', $referrerId)->first();
        }

        $totalReferrals = Referral::where('referrer_id', $referrerId)->count();
        $activeReferrals = Referral::where('referrer_id', $referrerId)->where('is_active', true)->count();

        $referrerStats->update([
            'total_referrals' => $totalReferrals,
            'active_referrals' => $activeReferrals,
            'last_updated_at' => now()
        ]);
    }

    /**
     * Initialize referral stats for a new referrer
     */
    public function initializeReferralStats(int $referrerId): void
    {
        ReferralStat::updateOrCreate(
            ['referrer_id' => $referrerId],
            [
                'total_referrals' => 0,
                'active_referrals' => 0,
                'total_commission_earned' => 0,
                'pending_commission' => 0,
                'paid_commission' => 0,
                'total_volume' => 0,
                'tier' => 'bronze',
                'tier_rate' => 0.01,
                'last_updated_at' => now()
            ]
        );
    }

    /**
     * Update referrer's tier based on referral count
     */
    public function updateReferrerTier(int $referrerId): void
    {
        $referrerStats = ReferralStat::where('referrer_id', $referrerId)->first();
        if (!$referrerStats) return;

        $newTier = ReferralStat::getTierName($referrerStats->total_referrals);
        $newRate = ReferralStat::getTierRate($referrerStats->total_referrals);

        if ($referrerStats->tier !== $newTier) {
            $referrerStats->update([
                'tier' => $newTier,
                'tier_rate' => $newRate
            ]);

            Log::info("Referrer tier updated", [
                'referrer_id' => $referrerId,
                'old_tier' => $referrerStats->tier,
                'new_tier' => $newTier,
                'new_rate' => $newRate
            ]);
        }
    }

    /**
     * Get referral statistics for a user
     */
    public function getReferralStats(int $userId): array
    {
        $stats = ReferralStat::where('referrer_id', $userId)->first();
        
        if (!$stats) {
            $this->initializeReferralStats($userId);
            $stats = ReferralStat::where('referrer_id', $userId)->first();
        }

        $referrals = Referral::where('referrer_id', $userId)
            ->with('referred')
            ->orderBy('created_at', 'desc')
            ->get();

        $recentCommissions = ReferralCommission::whereHas('referral', function($query) use ($userId) {
            $query->where('referrer_id', $userId);
        })
        ->with('transaction')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

        return [
            'stats' => $stats,
            'referrals' => $referrals,
            'recent_commissions' => $recentCommissions,
            'referral_link' => $this->generateReferralLink($userId)
        ];
    }

    /**
     * Generate referral link for a user
     */
    public function generateReferralLink(int $userId): string
    {
        $user = User::find($userId);
        if (!$user || !$user->referral_code) {
            return '';
        }

        $baseUrl = config('app.frontend_url', 'https://fadsms.com');
        return "{$baseUrl}/register?ref={$user->referral_code}";
    }

    /**
     * Pay pending commissions to referrer
     */
    public function payCommissions(int $referrerId): bool
    {
        try {
            DB::beginTransaction();

            $pendingCommissions = ReferralCommission::whereHas('referral', function($query) use ($referrerId) {
                $query->where('referrer_id', $referrerId);
            })
            ->where('status', 'pending')
            ->get();

            if ($pendingCommissions->isEmpty()) {
                DB::rollBack();
                return false;
            }

            $totalAmount = $pendingCommissions->sum('amount');
            $referrer = User::find($referrerId);

            // Update user balance
            $referrer->updateBalance($totalAmount, 'add');

            // Update commission status
            ReferralCommission::whereIn('id', $pendingCommissions->pluck('id'))
                ->update([
                    'status' => 'paid',
                    'paid_at' => now()
                ]);

            // Update referrer stats
            $referrerStats = ReferralStat::where('referrer_id', $referrerId)->first();
            if ($referrerStats) {
                $referrerStats->increment('paid_commission', $totalAmount);
                $referrerStats->decrement('pending_commission', $totalAmount);
                $referrerStats->increment('total_commission_earned', $totalAmount);
            }

            DB::commit();

            Log::info("Referral commissions paid", [
                'referrer_id' => $referrerId,
                'total_amount' => $totalAmount,
                'commission_count' => $pendingCommissions->count()
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to pay referral commissions", [
                'referrer_id' => $referrerId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
