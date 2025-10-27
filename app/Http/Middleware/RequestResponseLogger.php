<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RequestResponseLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $requestId = Str::uuid()->toString();
        
        // Log incoming request
        $this->logRequest($request, $requestId);
        
        // Process request
        $response = $next($request);
        
        // Log outgoing response
        $this->logResponse($response, $requestId, $startTime);
        
        return $response;
    }

    /**
     * Log incoming request details
     */
    private function logRequest(Request $request, string $requestId): void
    {
        $user = Auth::user();
        // Also check for Sanctum token authentication
        if (!$user && $request->bearerToken()) {
            $user = Auth::guard('sanctum')->user();
        }
        $userId = $user ? $user->id : 'guest';
        $userEmail = $user ? $user->email : 'anonymous';
        
        $logData = [
            'request_id' => $requestId,
            'type' => 'REQUEST',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $userId,
            'user_email' => $userEmail,
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'query_params' => $request->query->all(),
            'body' => $this->sanitizeBody($request->all()),
            'timestamp' => now()->toISOString(),
        ];

        // Log to different channels based on endpoint
        if ($this->isSmsEndpoint($request->path())) {
            Log::channel('sms')->info('SMS API Request', $logData);
        } elseif ($this->isAdminEndpoint($request->path())) {
            Log::channel('admin')->info('Admin API Request', $logData);
        } else {
            Log::info('API Request', $logData);
        }
    }

    /**
     * Log outgoing response details
     */
    private function logResponse($response, string $requestId, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2); // in milliseconds
        
        $responseData = [
            'request_id' => $requestId,
            'type' => 'RESPONSE',
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'headers' => $this->sanitizeHeaders($response->headers->all()),
            'body' => $this->sanitizeResponseBody($response->getContent()),
            'timestamp' => now()->toISOString(),
        ];

        // Log errors with higher priority
        if ($response->getStatusCode() >= 400) {
            $responseData['error'] = true;
            Log::error('API Error Response', $responseData);
        } else {
            // Log to different channels based on endpoint
            $request = request();
            if ($this->isSmsEndpoint($request->path())) {
                Log::channel('sms')->info('SMS API Response', $responseData);
            } elseif ($this->isAdminEndpoint($request->path())) {
                Log::channel('admin')->info('Admin API Response', $responseData);
            } else {
                Log::info('API Response', $responseData);
            }
        }
    }

    /**
     * Check if request is to SMS endpoints
     */
    private function isSmsEndpoint(string $path): bool
    {
        return str_starts_with($path, 'api/sms') || str_starts_with($path, 'sms');
    }

    /**
     * Check if request is to admin endpoints
     */
    private function isAdminEndpoint(string $path): bool
    {
        return str_starts_with($path, 'api/admin') || str_starts_with($path, 'admin');
    }

    /**
     * Sanitize headers to remove sensitive information
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key', 'x-auth-token'];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['***REDACTED***'];
            }
        }
        
        return $headers;
    }

    /**
     * Sanitize request body to remove sensitive information
     */
    private function sanitizeBody(array $body): array
    {
        $sensitiveFields = ['password', 'api_key', 'token', 'secret', 'key'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($body[$field])) {
                $body[$field] = '***REDACTED***';
            }
        }
        
        return $body;
    }

    /**
     * Sanitize response body for logging
     */
    private function sanitizeResponseBody(string $content): mixed
    {
        // Try to decode JSON
        $decoded = json_decode($content, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // Sanitize sensitive fields in response
            $sensitiveFields = ['api_key', 'token', 'secret', 'password'];
            
            foreach ($sensitiveFields as $field) {
                if (isset($decoded[$field])) {
                    $decoded[$field] = '***REDACTED***';
                }
            }
            
            return $decoded;
        }
        
        // For non-JSON responses, limit length
        return strlen($content) > 1000 ? substr($content, 0, 1000) . '...' : $content;
    }
}
