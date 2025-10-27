<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ApiKey;
use App\Models\ApiUsageLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ApiManagementController extends Controller
{
    /**
     * Get all API keys for authenticated user
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $keys = ApiKey::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $keys->map(function($key) {
                return [
                    'id' => $key->id,
                    'name' => $key->name,
                    'key' => $key->key,
                    'is_active' => $key->is_active,
                    'permissions' => $key->permissions,
                    'rate_limit_per_minute' => $key->rate_limit_per_minute,
                    'rate_limit_per_day' => $key->rate_limit_per_day,
                    'usage_count' => $key->usage_count,
                    'last_used_at' => $key->last_used_at?->toIso8601String(),
                    'expires_at' => $key->expires_at?->toIso8601String(),
                    'created_at' => $key->created_at->toIso8601String()
                ];
            })
        ]);
    }

    /**
     * Create new API key
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'rate_limit_per_minute' => 'nullable|integer|min:1|max:1000',
            'rate_limit_per_day' => 'nullable|integer|min:1|max:100000',
            'ip_whitelist' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // Check if user already has max keys (5)
        $existingCount = ApiKey::where('user_id', $user->id)->count();
        if ($existingCount >= 5) {
            return response()->json([
                'success' => false,
                'error' => 'Maximum API keys reached',
                'message' => 'You can only have up to 5 API keys. Please delete an existing key first.'
            ], 400);
        }

        $apiKey = ApiKey::generate(
            $user->id,
            $request->name,
            $request->permissions ?? []
        );

        if ($request->has('rate_limit_per_minute')) {
            $apiKey->rate_limit_per_minute = $request->rate_limit_per_minute;
        }
        if ($request->has('rate_limit_per_day')) {
            $apiKey->rate_limit_per_day = $request->rate_limit_per_day;
        }
        if ($request->has('ip_whitelist')) {
            $apiKey->ip_whitelist = $request->ip_whitelist;
        }
        
        $apiKey->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key' => $apiKey->key,
                'secret' => $apiKey->secret,
                'is_active' => $apiKey->is_active,
                'permissions' => $apiKey->permissions,
                'rate_limit_per_minute' => $apiKey->rate_limit_per_minute,
                'rate_limit_per_day' => $apiKey->rate_limit_per_day,
                'created_at' => $apiKey->created_at->toIso8601String()
            ],
            'message' => 'API key created successfully. Keep your key and secret safe!'
        ], 201);
    }

    /**
     * Update API key
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $apiKey = ApiKey::where('id', $id)->where('user_id', $user->id)->first();

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'API key not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'ip_whitelist' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('name')) $apiKey->name = $request->name;
        if ($request->has('is_active')) $apiKey->is_active = $request->is_active;
        if ($request->has('permissions')) $apiKey->permissions = $request->permissions;
        if ($request->has('ip_whitelist')) $apiKey->ip_whitelist = $request->ip_whitelist;

        $apiKey->save();

        return response()->json([
            'success' => true,
            'data' => $apiKey,
            'message' => 'API key updated successfully'
        ]);
    }

    /**
     * Delete API key
     */
    public function delete(int $id): JsonResponse
    {
        $user = Auth::user();
        $apiKey = ApiKey::where('id', $id)->where('user_id', $user->id)->first();

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'API key not found'
            ], 404);
        }

        $apiKey->delete();

        return response()->json([
            'success' => true,
            'message' => 'API key deleted successfully'
        ]);
    }

    /**
     * Get API usage statistics
     */
    public function getUsageStats(Request $request): JsonResponse
    {
        $user = Auth::user();
        $days = min((int) $request->input('days', 7), 30);

        $stats = ApiUsageLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as total_requests,
                SUM(CASE WHEN response_status < 400 THEN 1 ELSE 0 END) as successful_requests,
                SUM(CASE WHEN response_status >= 400 THEN 1 ELSE 0 END) as failed_requests,
                AVG(response_time_ms) as avg_response_time
            ')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        $endpointStats = ApiUsageLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                endpoint,
                COUNT(*) as requests,
                AVG(response_time_ms) as avg_response_time
            ')
            ->groupBy('endpoint')
            ->orderBy('requests', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'daily_stats' => $stats,
                'top_endpoints' => $endpointStats,
                'period_days' => $days
            ]
        ]);
    }
}



