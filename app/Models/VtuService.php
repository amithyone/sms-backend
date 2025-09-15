<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VtuService extends Model
{
    use HasFactory;

    protected $table = 'vtu_services';

    protected $fillable = [
        'name',
        'provider',
        'api_key',
        'username',
        'password',
        'pin',
        'api_url',
        'is_active',
        'balance',
        'last_balance_check',
        'settings',
        'priority',
        'success_rate',
        'total_orders',
        'successful_orders',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'balance' => 'decimal:2',
        'last_balance_check' => 'datetime',
        'settings' => 'array',
        'priority' => 'integer',
        'success_rate' => 'decimal:2',
        'total_orders' => 'integer',
        'successful_orders' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
