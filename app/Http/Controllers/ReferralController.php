<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ReferralService;
use App\Models\User;
use App\Models\ReferralCommission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReferralController extends Controller
{
    protected $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * Get user's referral statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $userId = Auth::id();
            $stats = $this->referralService->getReferralStats($userId);

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch referral stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get referral link
     */
    public function getReferralLink(): JsonResponse
    {
        try {
            $userId = Auth::id();
            $referralLink = $this->referralService->generateReferralLink($userId);
            
            $user = User::find($userId);
            $referralCode = $user->referral_code;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'referral_link' => $referralLink,
                    'referral_code' => $referralCode
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate referral link',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get referral commissions history
     */
    public function getCommissions(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);

            $commissions = ReferralCommission::whereHas('referral', function($query) use ($userId) {
                $query->where('referrer_id', $userId);
            })
            ->with(['referral.referred', 'transaction'])
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => 'success',
                'data' => $commissions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch commissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Request payout for pending commissions
     */
    public function requestPayout(): JsonResponse
    {
        try {
            $userId = Auth::id();
            $success = $this->referralService->payCommissions($userId);

            if ($success) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Commissions paid successfully'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No pending commissions to pay'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process payout',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate referral code (for registration)
     */
    public function validateReferralCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'referral_code' => 'required|string|exists:users,referral_code'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid referral code',
                'errors' => $validator->errors()
            ], 400);
        }

        $referralCode = $request->referral_code;
        $referrer = User::where('referral_code', $referralCode)->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'referrer_name' => $referrer->name,
                'referrer_username' => $referrer->username,
                'is_valid' => true
            ]
        ]);
    }

    /**
     * Generate referral code for user if they don't have one
     */
    public function generateReferralCode(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check if user already has a referral code
            if ($user->referral_code) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'You already have a referral code',
                    'data' => [
                        'referral_code' => $user->referral_code
                    ]
                ]);
            }

            // Generate unique referral code
            do {
                $code = strtoupper(substr(md5(uniqid($user->id, true)), 0, 8));
            } while (User::where('referral_code', $code)->exists());

            $user->referral_code = $code;
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Referral code generated successfully',
                'data' => [
                    'referral_code' => $code
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate referral code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get referral leaderboard
     */
    public function getLeaderboard(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            
            $leaderboard = \App\Models\ReferralStat::with('referrer')
                ->orderBy('total_commission_earned', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($stat, $index) {
                    return [
                        'rank' => $index + 1,
                        'user' => [
                            'name' => $stat->referrer->name,
                            'username' => $stat->referrer->username,
                            'avatar' => $stat->referrer->avatar
                        ],
                        'total_referrals' => $stat->total_referrals,
                        'total_commission_earned' => $stat->total_commission_earned,
                        'tier' => $stat->tier,
                        'tier_rate' => $stat->tier_rate
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $leaderboard
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch leaderboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tier information
     */
    public function getTierInfo(): JsonResponse
    {
        try {
            $tiers = [
                [
                    'name' => 'Bronze',
                    'referrals_required' => 0,
                    'commission_rate' => 0.01,
                    'benefits' => ['1% commission on referrals']
                ],
                [
                    'name' => 'Silver',
                    'referrals_required' => 20,
                    'commission_rate' => 0.011,
                    'benefits' => ['1.1% commission on referrals', 'Priority support']
                ],
                [
                    'name' => 'Gold',
                    'referrals_required' => 50,
                    'commission_rate' => 0.012,
                    'benefits' => ['1.2% commission on referrals', 'Priority support', 'Exclusive features']
                ],
                [
                    'name' => 'Platinum',
                    'referrals_required' => 100,
                    'commission_rate' => 0.015,
                    'benefits' => ['1.5% commission on referrals', 'Priority support', 'Exclusive features', 'Direct contact with team']
                ]
            ];

            return response()->json([
                'status' => 'success',
                'data' => $tiers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch tier information',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}