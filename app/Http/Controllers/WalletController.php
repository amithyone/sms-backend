<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\PayvibeService;

class WalletController extends Controller
{
    public function initiateTopUpPublic(Request $request, PayvibeService $payvibe): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|min:1',
            'amount' => 'required|numeric|min:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = (int) $request->input('user_id');
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $amount = (float) $request->input('amount');
        $result = $payvibe->initiateVirtualAccount($userId, $amount);

        if (!empty($result['success'])) {
            // Record a pending deposit for this user to aid later verification
            try {
                $data = $result['data'] ?? [];
                if (!empty($data['reference'])) {
                    DB::table('deposits')->updateOrInsert(
                        ['reference' => (string) $data['reference']],
                        [
                            'user_id' => $userId,
                            'amount' => $data['final_amount'] ?? $amount,
                            'status' => 'pending',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            } catch (\Throwable $e) {
                // Non-fatal for testing
            }

            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    public function initiateTopUp(Request $request, PayvibeService $payvibe): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $amount = (float) $request->input('amount');

        $result = $payvibe->initiateVirtualAccount($user->id, $amount);

        if (!empty($result['success'])) {
            // Optionally record a pending deposit for reconciliation
            try {
                $data = $result['data'] ?? [];
                if (!empty($data['reference'])) {
                    DB::table('deposits')->updateOrInsert(
                        ['reference' => (string) $data['reference']],
                        [
                            'user_id' => $user->id,
                            'amount' => $data['final_amount'] ?? $amount,
                            'status' => 'pending',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            } catch (\Throwable $e) {
                // Swallow DB errors to not block the flow
            }

            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    public function verifyTopUp(Request $request, PayvibeService $payvibe): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $reference = (string) $request->input('reference');
        $result = $payvibe->verifyPayment($reference);

        if (!empty($result['success'])) {
            $status = $result['data']['status'] ?? 'pending';
            $amount = $result['data']['amount'] ?? null;
            $user = Auth::user();

            try {
                if ($status === 'completed') {
                    // Mark deposit as completed and credit user balance if applicable
                    DB::transaction(function () use ($user, $reference, $amount) {
                        DB::table('deposits')
                            ->where('reference', $reference)
                            ->update(['status' => 'completed', 'updated_at' => now()]);

                        if (!is_null($amount)) {
                            DB::table('users')->where('id', $user->id)->increment('balance', (float)$amount);
                        }
                    });
                } elseif ($status === 'failed') {
                    DB::table('deposits')
                        ->where('reference', $reference)
                        ->update(['status' => 'failed', 'updated_at' => now()]);
                }
            } catch (\Throwable $e) {
                // Ignore accounting errors for this simple verify flow
            }

            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    public function getRecentDeposits(Request $request): JsonResponse
    {
        $user = Auth::user();
        $rows = DB::table('deposits')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $items = $rows->map(function ($d) {
            return [
                'id' => $d->id,
                'amount' => (float) $d->amount,
                'reference' => (string) $d->reference,
                'status' => (string) $d->status,
                'created_at' => (string) $d->created_at,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $items,
            'message' => 'Recent deposits retrieved successfully'
        ]);
    }
}
