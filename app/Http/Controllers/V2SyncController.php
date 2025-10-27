<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class V2SyncController extends Controller
{
    /**
     * Verify V2 sync API key
     */
    private function verifyApiKey(Request $request): bool
    {
        $apiKey = $request->header('X-V2-Sync-Key');
        $configKey = env('V2_SYNC_API_KEY');
        
        return $apiKey && $configKey && hash_equals($configKey, $apiKey);
    }

    /**
     * Get user data for V2 authentication
     */
    public function getUser(Request $request): JsonResponse
    {
        if (!$this->verifyApiKey($request)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized - Invalid API key'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        Log::info('V2 Sync: User data requested', [
            'email' => $request->email,
            'user_id' => $user->id
        ]);

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'wallet' => (float) $user->balance,
                'password_hash' => $user->password,
                'created_at' => $user->created_at->toIso8601String()
            ],
            'message' => 'User data retrieved successfully'
        ]);
    }

    /**
     * Verify if user exists
     */
    public function verifyUser(Request $request): JsonResponse
    {
        if (!$this->verifyApiKey($request)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $exists = User::where('email', $request->email)->exists();

        return response()->json([
            'status' => true,
            'exists' => $exists
        ]);
    }

    /**
     * Update user balance from V2 transactions
     */
    public function updateBalance(Request $request): JsonResponse
    {
        if (!$this->verifyApiKey($request)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:debit,credit',
            'description' => 'required|string',
            'reference' => 'required|string|unique:transactions,reference'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        $amount = (float) $request->amount;
        $type = $request->type;

        // Check balance for debit
        if ($type === 'debit' && $user->balance < $amount) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient balance',
                'required' => $amount,
                'available' => (float) $user->balance
            ], 400);
        }

        try {
            DB::beginTransaction();

            $oldBalance = $user->balance;

            // Update balance
            if ($type === 'debit') {
                $user->updateBalance($amount, 'subtract');
            } else {
                $user->updateBalance($amount, 'add');
            }

            // Record transaction
            DB::table('transactions')->insert([
                'user_id' => $user->id,
                'type' => $type === 'debit' ? 'service_purchase' : 'deposit',
                'amount' => $amount,
                'balance_before' => $oldBalance,
                'balance_after' => $user->balance,
                'description' => $request->description,
                'reference' => $request->reference,
                'status' => 'success',
                'metadata' => json_encode([
                    'source' => 'v2_sync',
                    'v2_reference' => $request->reference,
                    'sync_type' => $type
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            Log::info('V2 Sync: Balance updated', [
                'email' => $request->email,
                'amount' => $amount,
                'type' => $type,
                'old_balance' => $oldBalance,
                'new_balance' => $user->balance,
                'reference' => $request->reference
            ]);

            return response()->json([
                'status' => true,
                'data' => [
                    'old_balance' => (float) $oldBalance,
                    'new_balance' => (float) $user->balance,
                    'amount' => $amount,
                    'type' => $type
                ],
                'message' => 'Balance updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('V2 Sync: Balance update failed', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update balance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch get users
     */
    public function batchGetUsers(Request $request): JsonResponse
    {
        if (!$this->verifyApiKey($request)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'emails' => 'required|array|max:100',
            'emails.*' => 'email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $users = User::whereIn('email', $request->emails)->get();

        return response()->json([
            'status' => true,
            'data' => $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'wallet' => (float) $user->balance,
                    'created_at' => $user->created_at->toIso8601String()
                ];
            })
        ]);
    }

    /**
     * Create user from V2
     */
    public function createUser(Request $request): JsonResponse
    {
        if (!$this->verifyApiKey($request)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password_hash' => 'required|string',
            'phone' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password_hash,
                'phone' => $request->phone,
                'balance' => 0
            ]);

            Log::info('V2 Sync: User created', [
                'email' => $request->email,
                'user_id' => $user->id
            ]);

            return response()->json([
                'status' => true,
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email
                ],
                'message' => 'User created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create user: ' . $e->getMessage()
            ], 500);
        }
    }
}

