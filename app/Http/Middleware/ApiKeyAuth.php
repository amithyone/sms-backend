<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiKey;
use App\Models\ApiUsageLog;
use Illuminate\Support\Facades\Log;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $startTime = microtime(true);
        $apiKey = $request->header('X-API-Key') ?? $request->input('api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'API key required',
                'message' => 'Please provide API key in X-API-Key header or api_key parameter'
            ], 401);
        }

        $key = ApiKey::where('key', $apiKey)->first();

        if (!$key) {
            $this->logUsage(null, $request, 401, null, 'Invalid API key', $startTime);
            return response()->json([
                'success' => false,
                'error' => 'Invalid API key',
                'message' => 'The provided API key is not valid'
            ], 401);
        }

        if (!$key->isValid()) {
            $this->logUsage($key, $request, 403, null, 'API key inactive or expired', $startTime);
            return response()->json([
                'success' => false,
                'error' => 'API key inactive or expired',
                'message' => 'This API key is no longer active'
            ], 403);
        }

        // Check IP whitelist
        if ($key->ip_whitelist) {
            $allowedIps = array_map('trim', explode(',', $key->ip_whitelist));
            $clientIp = $request->ip();
            
            if (!in_array($clientIp, $allowedIps) && !in_array('*', $allowedIps)) {
                $this->logUsage($key, $request, 403, null, 'IP not whitelisted', $startTime);
                return response()->json([
                    'success' => false,
                    'error' => 'IP not allowed',
                    'message' => 'Your IP address is not whitelisted for this API key'
                ], 403);
            }
        }

        // Check permissions
        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                if (!$key->hasPermission($permission)) {
                    $this->logUsage($key, $request, 403, null, "Missing permission: {$permission}", $startTime);
                    return response()->json([
                        'success' => false,
                        'error' => 'Insufficient permissions',
                        'message' => "This API key does not have '{$permission}' permission"
                    ], 403);
                }
            }
        }

        // Check rate limit
        if (!$key->checkRateLimit()) {
            $this->logUsage($key, $request, 429, null, 'Rate limit exceeded', $startTime);
            return response()->json([
                'success' => false,
                'error' => 'Rate limit exceeded',
                'message' => 'You have exceeded the API rate limit. Please try again later.'
            ], 429);
        }

        // Attach API key and user to request
        $request->attributes->set('api_key', $key);
        $request->attributes->set('api_user', $key->user);

        // Record usage
        $key->recordUsage();

        // Continue to controller
        $response = $next($request);

        // Log usage after response
        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000); // Convert to ms
        $this->logUsage($key, $request, $response->status(), $response->getData(true), null, $startTime);

        return $response;
    }

    private function logUsage($key, Request $request, int $status, $responseData, $error, $startTime)
    {
        try {
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000);

            ApiUsageLog::create([
                'user_id' => $key->user_id ?? null,
                'api_key_id' => $key->id ?? null,
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'response_status' => $status,
                'response_time_ms' => $responseTime,
                'request_data' => $request->except(['api_key', 'password', 'secret']),
                'response_data' => is_array($responseData) ? array_slice($responseData, 0, 10) : null,
                'error_message' => $error,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log API usage', ['error' => $e->getMessage()]);
        }
    }
}



