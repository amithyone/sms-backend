<?php

namespace App\Http\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Standard success response
     */
    public static function success($data = null, string $message = 'Operation successful', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Standard error response
     */
    public static function error(string $message = 'Operation failed', $errors = null, int $statusCode = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response
     */
    public static function validationError($errors, string $message = 'Validation failed'): JsonResponse
    {
        return self::error($message, $errors, 422);
    }

    /**
     * Not found response
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, null, 404);
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized access'): JsonResponse
    {
        return self::error($message, null, 401);
    }

    /**
     * Forbidden response
     */
    public static function forbidden(string $message = 'Access forbidden'): JsonResponse
    {
        return self::error($message, null, 403);
    }

    /**
     * Server error response
     */
    public static function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return self::error($message, null, 500);
    }

    /**
     * Service unavailable response
     */
    public static function serviceUnavailable(string $message = 'Service temporarily unavailable'): JsonResponse
    {
        return self::error($message, null, 503);
    }

    /**
     * Paginated response
     */
    public static function paginated($data, $pagination, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return self::success([
            'items' => $data,
            'pagination' => $pagination
        ], $message);
    }

    /**
     * List response with metadata
     */
    public static function list($data, array $metadata = [], string $message = 'List retrieved successfully'): JsonResponse
    {
        $response = [
            'items' => $data,
            'count' => count($data),
        ];

        if (!empty($metadata)) {
            $response['metadata'] = $metadata;
        }

        return self::success($response, $message);
    }

    /**
     * Service status response
     */
    public static function serviceStatus(string $service, string $status, $details = null, string $message = null): JsonResponse
    {
        $response = [
            'service' => $service,
            'status' => $status,
            'timestamp' => now()->toISOString(),
        ];

        if ($details !== null) {
            $response['details'] = $details;
        }

        if ($message) {
            $response['message'] = $message;
        }

        $statusCode = $status === 'healthy' ? 200 : 503;
        
        return response()->json($response, $statusCode);
    }

    /**
     * Transaction response
     */
    public static function transaction($transactionData, string $message = 'Transaction completed'): JsonResponse
    {
        $response = [
            'transaction' => $transactionData,
            'reference' => $transactionData['reference'] ?? null,
            'status' => $transactionData['status'] ?? 'pending',
        ];

        return self::success($response, $message);
    }

    /**
     * Order response
     */
    public static function order($orderData, string $message = 'Order created successfully'): JsonResponse
    {
        $response = [
            'order' => $orderData,
            'order_id' => $orderData['order_id'] ?? null,
            'status' => $orderData['status'] ?? 'pending',
        ];

        return self::success($response, $message);
    }

    /**
     * Balance response
     */
    public static function balance($balanceData, string $message = 'Balance retrieved successfully'): JsonResponse
    {
        $response = [
            'balance' => $balanceData['balance'] ?? 0,
            'currency' => $balanceData['currency'] ?? 'NGN',
            'provider' => $balanceData['provider'] ?? null,
        ];

        return self::success($response, $message);
    }

    /**
     * Network/Service list response
     */
    public static function networkList($networks, string $type = 'networks', string $message = null): JsonResponse
    {
        $message = $message ?? ucfirst($type) . ' retrieved successfully';
        
        return self::success([
            $type => $networks,
            'count' => count($networks)
        ], $message);
    }

    /**
     * Service bundles response
     */
    public static function bundles($bundles, string $network, string $message = null): JsonResponse
    {
        $message = $message ?? "Data bundles for {$network} retrieved successfully";
        
        return self::success([
            'network' => $network,
            'bundles' => $bundles,
            'count' => count($bundles)
        ], $message);
    }

    /**
     * Health check response
     */
    public static function health($healthData, string $overallStatus = 'healthy'): JsonResponse
    {
        $statusCode = $overallStatus === 'healthy' ? 200 : 503;
        
        return response()->json([
            'status' => $overallStatus,
            'timestamp' => now()->toISOString(),
            'services' => $healthData['services'] ?? [],
            'database' => $healthData['database'] ?? null,
            'cache' => $healthData['cache'] ?? null,
            'overall_status' => $overallStatus
        ], $statusCode);
    }
}

