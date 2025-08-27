<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * User registration
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20|unique:users',
            'username' => 'nullable|string|max:50|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'referral_code' => 'nullable|string|exists:users,referral_code',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        
        // Find referrer if referral code provided
        $referredBy = null;
        if (!empty($data['referral_code'])) {
            $referredBy = User::where('referral_code', $data['referral_code'])->first();
        }

        // Create user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'username' => $data['username'] ?? null,
            'password' => Hash::make($data['password']),
            'referral_code' => Str::random(8),
            'referred_by' => $referredBy?->id,
            'balance' => 0.00,
            'wallet_balance' => 0.00,
            'status' => 'active',
            'role' => 'user',
        ]);

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'phone', 'username', 'balance', 'wallet_balance', 'referral_code']),
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ], 201);
    }

    /**
     * User login
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $validator->validated();

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();

        if (!$user->isActive()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Account is not active'
            ], 403);
        }

        // Update last login
        $user->updateLastLogin($request->ip());

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'phone', 'username', 'balance', 'wallet_balance', 'referral_code', 'role']),
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    /**
     * User logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get user profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get recent transactions
        $recentTransactions = $user->transactions()
            ->latest()
            ->take(5)
            ->get(['id', 'type', 'amount', 'description', 'status', 'created_at']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'phone', 'username', 'balance', 'wallet_balance', 'referral_code', 'role', 'status', 'last_login_at']),
                'recent_transactions' => $recentTransactions
            ]
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20|unique:users,phone,' . $user->id,
            'username' => 'sometimes|string|max:50|unique:users,username,' . $user->id,
            'account_number' => 'sometimes|string|max:20|unique:users,account_number,' . $user->id,
            'bank_name' => 'sometimes|string|max:100',
            'account_name' => 'sometimes|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'phone', 'username', 'balance', 'wallet_balance', 'referral_code', 'role', 'account_number', 'bank_name', 'account_name'])
            ]
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password is incorrect'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Get user transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $transactions = $user->transactions()
            ->with('service:id,name')
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $transactions
        ]);
    }
}
