<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CachedSmsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CachedSmsController extends Controller
{
    /**
     * Get countries with cached services
     */
    public function getCountries(): JsonResponse
    {
        try {
            $countries = CachedSmsService::getAvailableCountries();
            
            return response()->json([
                'success' => true,
                'data' => $countries->map(function ($country) {
                    return [
                        'code' => $country->country_code,
                        'name' => $country->country_name,
                        'provider_count' => $country->provider_count,
                        'service_count' => $country->service_count,
                        'flag' => $this->getCountryFlag($country->country_code),
                        'provider' => 'cached'
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch countries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get services for a country from cache
     */
    public function getServices(Request $request): JsonResponse
    {
        try {
            $countryCode = $request->get('country', 'US');
            
            // Get services grouped by provider
            $servicesByProvider = CachedSmsService::getServicesByProviderForCountry($countryCode);
            
            if ($servicesByProvider->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No services available for this country'
                ], 404);
            }

            // Update provider names with actual brand names from SMS services table
            $servicesByProvider = $servicesByProvider->map(function ($providerGroup) {
                $actualBrandName = $this->getActualProviderBrandName($providerGroup['provider']);
                
                return [
                    'provider' => $providerGroup['provider'],
                    'provider_name' => $actualBrandName,
                    'services' => array_map(function ($service) use ($actualBrandName) {
                        return array_merge($service, ['provider_name' => $actualBrandName]);
                    }, $providerGroup['services'])
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $servicesByProvider,
                'country' => $countryCode,
                'total_providers' => $servicesByProvider->count(),
                'total_services' => $servicesByProvider->sum(function ($provider) {
                    return count($provider['services']);
                })
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch services: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get actual provider brand name from SMS services table
     */
    private function getActualProviderBrandName($provider)
    {
        $smsService = \DB::table('sms_services')
            ->where('provider', $provider)
            ->select('name')
            ->first();
            
        return $smsService ? $smsService->name : ucfirst(str_replace('_', ' ', $provider));
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): JsonResponse
    {
        try {
            $smsCacheService = new \App\Services\SmsCacheService();
            $stats = $smsCacheService->getCacheStats();
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch cache stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search services across all providers
     */
    public function searchServices(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            $countryCode = $request->get('country', '');
            
            $servicesQuery = CachedSmsService::where('status', 'active');
            
            if ($countryCode) {
                $servicesQuery->where('country_code', $countryCode);
            }
            
            if ($query) {
                $servicesQuery->where(function ($q) use ($query) {
                    $q->where('service_name', 'like', "%{$query}%")
                      ->orWhere('service_code', 'like', "%{$query}%");
                });
            }
            
            $services = $servicesQuery->orderBy('service_name')
                ->orderBy('cost_ngn')
                ->get()
                ->groupBy('service_name')
                ->map(function ($serviceGroup) {
                    return [
                        'service_name' => $serviceGroup->first()->service_name,
                        'service_code' => $serviceGroup->first()->service_code,
                        'providers' => $serviceGroup->map(function ($service) {
                            return [
                                'provider' => $service->provider,
                                'provider_name' => $service->provider_name,
                                'cost_ngn' => $service->cost_ngn,
                                'available_count' => $service->available_count,
                                'country_code' => $service->country_code,
                                'country_name' => $service->country_name,
                            ];
                        })->sortBy('cost_ngn')->values()
                    ];
                })->values();

            return response()->json([
                'success' => true,
                'data' => $services,
                'query' => $query,
                'country' => $countryCode,
                'total_services' => $services->count()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search services: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get country flag emoji
     */
    private function getCountryFlag($countryCode)
    {
        $flags = [
            'US' => '🇺🇸', 'GB' => '🇬🇧', 'CA' => '🇨🇦', 'NG' => '🇳🇬',
            'DE' => '🇩🇪', 'FR' => '🇫🇷', 'IT' => '🇮🇹', 'ES' => '🇪🇸',
            'NL' => '🇳🇱', 'SE' => '🇸🇪', 'NO' => '🇳🇴', 'DK' => '🇩🇰',
            'FI' => '🇫🇮', 'JP' => '🇯🇵', 'KR' => '🇰🇷', 'CN' => '🇨🇳',
            'BR' => '🇧🇷', 'ZA' => '🇿🇦', 'AE' => '🇦🇪', 'TR' => '🇹🇷',
            'SA' => '🇸🇦', 'IN' => '🇮🇳', 'AU' => '🇦🇺', 'RU' => '🇷🇺',
        ];
        
        return $flags[$countryCode] ?? '🌍';
    }
}
