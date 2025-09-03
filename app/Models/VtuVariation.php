<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VtuVariation extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_type',
        'provider',
        'network',
        'variation_code',
        'name',
        'description',
        'price',
        'unit',
        'validity_days',
        'features',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'validity_days' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByServiceType($query, $serviceType)
    {
        return $query->where('service_type', $serviceType);
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeByNetwork($query, $network)
    {
        return $query->where('network', $network);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
