<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ErrorLoggerController extends Controller
{
    /**
     * Log frontend errors
     */
    public function logFrontendError(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'error' => 'required|string',
            'stack' => 'nullable|string',
            'url' => 'nullable|string',
            'line' => 'nullable|integer',
            'column' => 'nullable|integer',
            'user_agent' => 'nullable|string',
            'timestamp' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'session_id' => 'nullable|string',
            'component' => 'nullable|string',
            'action' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        $userId = $user ? $user->id : null;
        $userEmail = $user ? $user->email : null;

        $errorData = [
            'type' => 'FRONTEND_ERROR',
            'error' => $request->error,
            'stack' => $request->stack,
            'url' => $request->url,
            'line' => $request->line,
            'column' => $request->column,
            'user_agent' => $request->user_agent ?? $request->header('User-Agent'),
            'timestamp' => $request->timestamp ?? now()->toISOString(),
            'user_id' => $userId,
            'user_email' => $userEmail,
            'session_id' => $request->session_id,
            'component' => $request->component,
            'action' => $request->action,
            'metadata' => $request->metadata ?? [],
            'ip' => $request->ip(),
            'request_id' => $request->header('X-Request-ID'),
        ];

        // Log to frontend error channel
        Log::channel('frontend')->error('Frontend Error', $errorData);

        // Also log critical errors to main error log
        if ($this->isCriticalError($request->error)) {
            Log::channel('errors')->critical('Critical Frontend Error', $errorData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Error logged successfully'
        ]);
    }

    /**
     * Log frontend performance issues
     */
    public function logPerformanceIssue(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'metric' => 'required|string',
            'value' => 'required|numeric',
            'threshold' => 'nullable|numeric',
            'component' => 'nullable|string',
            'action' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        $userId = $user ? $user->id : null;

        $performanceData = [
            'type' => 'FRONTEND_PERFORMANCE',
            'metric' => $request->metric,
            'value' => $request->value,
            'threshold' => $request->threshold,
            'component' => $request->component,
            'action' => $request->action,
            'metadata' => $request->metadata ?? [],
            'user_id' => $userId,
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('performance')->warning('Frontend Performance Issue', $performanceData);

        return response()->json([
            'success' => true,
            'message' => 'Performance issue logged successfully'
        ]);
    }

    /**
     * Log API usage analytics
     */
    public function logApiUsage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|string',
            'method' => 'required|string',
            'duration' => 'required|numeric',
            'status_code' => 'required|integer',
            'response_size' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $usageData = [
            'type' => 'API_USAGE',
            'endpoint' => $request->endpoint,
            'method' => $request->method,
            'duration' => $request->duration,
            'status_code' => $request->status_code,
            'response_size' => $request->response_size,
            'user_id' => $request->user_id,
            'metadata' => $request->metadata ?? [],
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('performance')->info('API Usage', $usageData);

        return response()->json([
            'success' => true,
            'message' => 'API usage logged successfully'
        ]);
    }

    /**
     * Check if error is critical
     */
    private function isCriticalError(string $error): bool
    {
        $criticalPatterns = [
            'network error',
            'connection failed',
            'timeout',
            'cors error',
            'authentication failed',
            'unauthorized',
            'forbidden',
            'server error',
            'internal error',
        ];

        $errorLower = strtolower($error);
        
        foreach ($criticalPatterns as $pattern) {
            if (strpos($errorLower, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
