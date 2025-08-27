<?php

namespace App\Http\Controllers;

use App\Services\WebshareService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProxyController extends Controller
{
    private $webshareService;

    public function __construct(WebshareService $webshareService)
    {
        $this->webshareService = $webshareService;
    }

    /**
     * Get user profile
     */
    public function getUserProfile(): JsonResponse
    {
        try {
            $result = $this->webshareService->getUserProfile();

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get proxy list
     */
    public function getProxyList(Request $request): JsonResponse
    {
        try {
            $params = $request->all();
            $result = $this->webshareService->getProxyList($params);

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get proxy list: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download proxy list
     */
    public function downloadProxyList(Request $request): JsonResponse
    {
        try {
            $params = $request->all();
            $result = $this->webshareService->downloadProxyList($params);

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download proxy list: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh proxy list
     */
    public function refreshProxyList(): JsonResponse
    {
        try {
            $result = $this->webshareService->refreshProxyList();

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh proxy list: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get proxy statistics
     */
    public function getProxyStats(Request $request): JsonResponse
    {
        try {
            $params = $request->all();
            $result = $this->webshareService->getProxyStats($params);

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get proxy statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get subscription plans
     */
    public function getSubscriptionPlans(): JsonResponse
    {
        try {
            $result = $this->webshareService->getSubscriptionPlans();

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get subscription plans: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available assets
     */
    public function getAvailableAssets(): JsonResponse
    {
        try {
            $result = $this->webshareService->getAvailableAssets();

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get available assets: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get proxy configuration
     */
    public function getProxyConfig(): JsonResponse
    {
        try {
            $result = $this->webshareService->getProxyConfig();

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get proxy configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create IP authorization
     */
    public function createIpAuthorization(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ip' => 'required|ip'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->webshareService->createIpAuthorization($request->ip);

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create IP authorization: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get IP authorizations
     */
    public function getIpAuthorizations(): JsonResponse
    {
        try {
            $result = $this->webshareService->getIpAuthorizations();

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get IP authorizations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete IP authorization
     */
    public function deleteIpAuthorization(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'authorization_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->webshareService->deleteIpAuthorization($request->authorization_id);

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete IP authorization: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get "What's my IP" information
     */
    public function getMyIp(): JsonResponse
    {
        try {
            $result = $this->webshareService->getMyIp();

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get IP information: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get proxy activities
     */
    public function getProxyActivities(Request $request): JsonResponse
    {
        try {
            $params = $request->all();
            $result = $this->webshareService->getProxyActivities($params);

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get proxy activities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download proxy activities
     */
    public function downloadProxyActivities(Request $request): JsonResponse
    {
        try {
            $params = $request->all();
            $result = $this->webshareService->downloadProxyActivities($params);

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download proxy activities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Purchase proxy plan
     */
    public function purchaseProxyPlan(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|string',
            'payment_method' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            
            // Here you would implement the actual purchase logic
            // This is a placeholder for the purchase flow
            
            // Create transaction record
            $user->transactions()->create([
                'type' => 'proxy_purchase',
                'amount' => 0, // Set actual amount based on plan
                'balance_before' => $user->balance,
                'balance_after' => $user->balance,
                'description' => "Proxy plan purchase: {$request->plan_id}",
                'reference' => 'PROXY_' . uniqid(),
                'status' => 'pending',
                'metadata' => [
                    'plan_id' => $request->plan_id,
                    'payment_method' => $request->payment_method,
                    'provider' => 'webshare'
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Proxy plan purchase initiated successfully',
                'data' => [
                    'plan_id' => $request->plan_id,
                    'status' => 'pending'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to purchase proxy plan: ' . $e->getMessage()
            ], 500);
        }
    }
}
