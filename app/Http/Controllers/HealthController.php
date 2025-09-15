<?php

namespace App\Http\Controllers;

use App\Services\DatabaseVtuService;
use App\Services\SmsProviderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;

class HealthController extends Controller
{
    private $vtuService;
    private $smsProviderService;

    public function __construct(DatabaseVtuService $vtuService, SmsProviderService $smsProviderService)
    {
        $this->vtuService = $vtuService;
        $this->smsProviderService = $smsProviderService;
    }

    /**
     * Comprehensive health check for all API services
     */
    public function check(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'services' => [],
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'overall_status' => 'healthy'
        ];

        // Check VTU services
        $health['services']['vtu'] = $this->checkVtuServices();
        
        // Check SMS services
        $health['services']['sms'] = $this->checkSmsServices();
        
        // Check payment services
        $health['services']['payment'] = $this->checkPaymentServices();
        
        // Determine overall status
        $allServicesHealthy = true;
        foreach ($health['services'] as $service) {
            if ($service['status'] !== 'healthy') {
                $allServicesHealthy = false;
                break;
            }
        }
        
        if (!$health['database']['connected'] || !$health['cache']['working']) {
            $allServicesHealthy = false;
        }
        
        $health['overall_status'] = $allServicesHealthy ? 'healthy' : 'degraded';
        $health['status'] = $health['overall_status'];

        $statusCode = $allServicesHealthy ? 200 : 503;
        
        return response()->json($health, $statusCode);
    }

    /**
     * Quick health check (minimal checks)
     */
    public function quick(): JsonResponse
    {
        try {
            // Basic database check
            DB::connection()->getPdo();
            $dbHealthy = true;
        } catch (Exception $e) {
            $dbHealthy = false;
        }

        $status = $dbHealthy ? 'healthy' : 'unhealthy';
        $statusCode = $dbHealthy ? 200 : 503;

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toISOString(),
            'database' => $dbHealthy ? 'connected' : 'disconnected'
        ], $statusCode);
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            return [
                'connected' => true,
                'response_time_ms' => $responseTime,
                'status' => 'healthy'
            ];
        } catch (Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
                'status' => 'unhealthy'
            ];
        }
    }

    /**
     * Check cache functionality
     */
    private function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            return [
                'working' => $retrieved === $testValue,
                'status' => $retrieved === $testValue ? 'healthy' : 'unhealthy'
            ];
        } catch (Exception $e) {
            return [
                'working' => false,
                'error' => $e->getMessage(),
                'status' => 'unhealthy'
            ];
        }
    }

    /**
     * Check VTU services
     */
    private function checkVtuServices(): array
    {
        $services = [];
        $overallHealthy = true;

        try {
            // Check VTU.ng
            $vtuNgResult = $this->vtuService->getBalance();
            $services['vtu_ng'] = [
                'status' => $vtuNgResult['success'] ? 'healthy' : 'unhealthy',
                'balance' => $vtuNgResult['balance'] ?? 0,
                'message' => $vtuNgResult['message'] ?? 'Unknown status'
            ];
            
            if (!$vtuNgResult['success']) {
                $overallHealthy = false;
            }
        } catch (Exception $e) {
            $services['vtu_ng'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $overallHealthy = false;
        }

        // Check iRecharge (if configured)
        try {
            $irechargeResult = app(\App\Services\IrechargeService::class)->getBalance();
            $services['irecharge'] = [
                'status' => $irechargeResult['success'] ? 'healthy' : 'unhealthy',
                'balance' => $irechargeResult['balance'] ?? 0,
                'message' => $irechargeResult['message'] ?? 'Unknown status'
            ];
            
            if (!$irechargeResult['success']) {
                $overallHealthy = false;
            }
        } catch (Exception $e) {
            $services['irecharge'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $overallHealthy = false;
        }

        return [
            'status' => $overallHealthy ? 'healthy' : 'degraded',
            'services' => $services
        ];
    }

    /**
     * Check SMS services
     */
    private function checkSmsServices(): array
    {
        $services = [];
        $overallHealthy = true;

        try {
            $smsServices = \App\Models\SmsService::active()->get();
            
            foreach ($smsServices as $smsService) {
                try {
                    // Try to get countries as a health check
                    $countries = $this->smsProviderService->getCountries($smsService);
                    $services[$smsService->provider] = [
                        'status' => !empty($countries) ? 'healthy' : 'degraded',
                        'countries_available' => count($countries),
                        'success_rate' => $smsService->success_rate,
                        'total_orders' => $smsService->total_orders
                    ];
                    
                    if (empty($countries)) {
                        $overallHealthy = false;
                    }
                } catch (Exception $e) {
                    $services[$smsService->provider] = [
                        'status' => 'unhealthy',
                        'error' => $e->getMessage()
                    ];
                    $overallHealthy = false;
                }
            }
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => 'Failed to check SMS services: ' . $e->getMessage()
            ];
        }

        return [
            'status' => $overallHealthy ? 'healthy' : 'degraded',
            'services' => $services
        ];
    }

    /**
     * Check payment services
     */
    private function checkPaymentServices(): array
    {
        $services = [];
        $overallHealthy = true;

        // Check PayVibe configuration
        $payvibeConfig = config('services.payment.payvibe');
        if ($payvibeConfig && !empty($payvibeConfig['public_key']) && !empty($payvibeConfig['secret_key'])) {
            $services['payvibe'] = [
                'status' => 'configured',
                'base_url' => $payvibeConfig['base_url'] ?? 'Not set'
            ];
        } else {
            $services['payvibe'] = [
                'status' => 'not_configured',
                'message' => 'Missing API keys'
            ];
            $overallHealthy = false;
        }

        return [
            'status' => $overallHealthy ? 'healthy' : 'degraded',
            'services' => $services
        ];
    }

    /**
     * Get API endpoints status
     */
    public function endpoints(): JsonResponse
    {
        $endpoints = [
            'public' => [
                'GET /api/vtu/services' => 'VTU Services List',
                'GET /api/vtu/airtime/networks' => 'Airtime Networks',
                'GET /api/vtu/data/networks' => 'Data Networks',
                'GET /api/sms/providers' => 'SMS Providers',
                'GET /api/sms/countries' => 'SMS Countries',
                'POST /api/register' => 'User Registration',
                'POST /api/login' => 'User Login'
            ],
            'protected' => [
                'POST /api/vtu/airtime/purchase' => 'Purchase Airtime',
                'POST /api/vtu/data/purchase' => 'Purchase Data Bundle',
                'POST /api/sms/order' => 'Create SMS Order',
                'GET /api/sms/orders' => 'Get SMS Orders',
                'GET /api/vtu/transactions' => 'Get Transactions',
                'POST /api/wallet/topup/initiate' => 'Initiate Wallet Top-up'
            ],
            'health' => [
                'GET /api/health' => 'Full Health Check',
                'GET /api/health/quick' => 'Quick Health Check',
                'GET /api/health/endpoints' => 'API Endpoints List'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $endpoints,
            'message' => 'API endpoints retrieved successfully'
        ]);
    }
}

