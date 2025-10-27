<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;

class LogVtuSmsRequests
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $path = $request->path();

        // Allow toggling via env(LOG_VTU_SMS=true)
        $enabled = (bool) Config::get('services.logging.vtu_sms', false);
        if (!$enabled) {
            return $next($request);
        }

        // Only log VTU and SMS related routes under /api
        if (!Str::startsWith($path, ['api/vtu', 'api/sms'])) {
            return $next($request);
        }

        $requestId = $request->headers->get('X-Request-ID') ?: (string) Str::uuid();
        // Ensure the request ID is available downstream
        $request->headers->set('X-Request-ID', $requestId);

        // Mask sensitive fields if present
        $payload = $request->all();
        foreach (['password', 'pin', 'token', 'api_key', 'secret'] as $sensitive) {
            if (array_key_exists($sensitive, $payload)) {
                $payload[$sensitive] = '***';
            }
        }

        Log::info('VTU/SMS request', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $path,
            'ip' => $request->ip(),
            'query' => $request->query(),
            'payload' => $payload,
            'headers' => [
                'user-agent' => $request->userAgent(),
                'content-type' => $request->headers->get('content-type'),
            ],
        ]);

        $response = $next($request);

        // Capture a safe slice of the response
        $responseData = null;
        try {
            $contentType = $response->headers->get('content-type');
            if ($contentType && str_contains($contentType, 'application/json')) {
                $decoded = json_decode($response->getContent(), true);
                // Avoid logging huge payloads
                $responseData = is_array($decoded) ? array_slice($decoded, 0, 50, true) : null;
            }
        } catch (\Throwable $e) {
            $responseData = null;
        }

        // Propagate the request id to the client
        $response->headers->set('X-Request-ID', $requestId);

        Log::info('VTU/SMS response', [
            'request_id' => $requestId,
            'status' => $response->getStatusCode(),
            'path' => $path,
            'response_sample' => $responseData,
        ]);

        return $response;
    }
}


