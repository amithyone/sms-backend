<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CachedSmsService extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'provider_name',
        'brand_name',
        'country_code',
        'country_name',
        'service_code',
        'service_name',
        'cost_ngn',
        'original_cost',
        'original_currency',
        'available_count',
        'is_popular',
        'status',
        'metadata',
        'last_updated',
    ];

    protected $casts = [
        'cost_ngn' => 'decimal:2',
        'original_cost' => 'decimal:2',
        'available_count' => 'integer',
        'is_popular' => 'boolean',
        'metadata' => 'array',
        'last_updated' => 'datetime',
    ];

    /**
     * Scope to get services for a specific country
     */
    public function scopeForCountry($query, $countryCode)
    {
        $query = $query->where('country_code', $countryCode);
        
        // Only filter by status if the status column exists
        if (\Schema::hasColumn($this->getTable(), 'status')) {
            $query = $query->where('status', 'active');
        }
        
        return $query;
    }

    /**
     * Scope to get services for a specific provider
     */
    public function scopeForProvider($query, $provider)
    {
        $query = $query->where('provider', $provider);
        
        // Only filter by status if the status column exists
        if (\Schema::hasColumn($this->getTable(), 'status')) {
            $query = $query->where('status', 'active');
        }
        
        return $query;
    }

    /**
     * Scope to get services by name (e.g., WhatsApp)
     */
    public function scopeByName($query, $serviceName)
    {
        return $query->where('service_name', 'like', '%' . $serviceName . '%')
                    ->orWhere('service_code', 'like', '%' . $serviceName . '%');
    }

    /**
     * Scope to get popular services
     */
    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    /**
     * Scope to get fresh data (updated within last 2 hours)
     */
    public function scopeFresh($query)
    {
        return $query->where('last_updated', '>=', now()->subHours(2));
    }

    /**
     * Get services grouped by provider for a country
     */
    public static function getServicesByProviderForCountry($countryCode)
    {
        return self::forCountry($countryCode)
            ->orderBy('service_name')
            ->orderBy('cost_ngn')
            ->get()
            ->groupBy('provider')
            ->map(function ($services) {
                return [
                    'provider' => $services->first()->provider,
                    'provider_name' => $services->first()->brand_name ?? $services->first()->provider_name,
                    'services' => $services->map(function ($service) {
                        return [
                            'id' => $service->id,
                            'name' => $service->service_name,
                            'service' => $service->service_code,
                            'cost' => $service->cost_ngn,
                            'count' => $service->available_count,
                            'provider' => $service->provider,
                            'provider_name' => $service->brand_name ?? $service->provider_name,
                            'is_popular' => $service->is_popular ?? false,
                            'status' => $service->status ?? 'active',
                            'currency' => 'NGN',
                        ];
                    })->toArray()
                ];
            })->values();
    }

    /**
     * Get all unique countries with service counts
     */
    public static function getAvailableCountries()
    {
        $query = self::select('country_code', 'country_name')
            ->selectRaw('COUNT(DISTINCT provider) as provider_count')
            ->selectRaw('COUNT(*) as service_count');
            
        // Only filter by status if the status column exists
        if (\Schema::hasColumn((new self)->getTable(), 'status')) {
            $query = $query->where('status', 'active');
        }
        
        return $query->groupBy('country_code', 'country_name')
            ->orderBy('country_name')
            ->get();
    }
}