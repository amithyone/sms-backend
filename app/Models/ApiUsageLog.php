<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiUsageLog extends Model
{
    protected $fillable = [
        'user_id',
        'api_key_id',
        'endpoint',
        'method',
        'ip_address',
        'response_status',
        'response_time_ms',
        'request_data',
        'response_data',
        'error_message'
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class);
    }
}



