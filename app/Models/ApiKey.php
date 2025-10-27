<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'key',
        'secret',
        'is_active',
        'permissions',
        'rate_limit_per_minute',
        'rate_limit_per_day',
        'ip_whitelist',
        'last_used_at',
        'usage_count',
        'expires_at'
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function usageLogs()
    {
        return $this->hasMany(ApiUsageLog::class);
    }

    /**
     * Generate a new API key
     */
    public static function generate(int $userId, string $name, array $permissions = []): self
    {
        return self::create([
            'user_id' => $userId,
            'name' => $name,
            'key' => 'fad_' . Str::random(32),
            'secret' => Str::random(64),
            'permissions' => $permissions,
            'is_active' => true,
        ]);
    }

    /**
     * Check if key has permission
     */
    public function hasPermission(string $permission): bool
    {
        if (empty($this->permissions)) {
            return true; // No restrictions
        }
        
        return in_array($permission, $this->permissions);
    }

    /**
     * Check if key is valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Record usage
     */
    public function recordUsage()
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Check rate limit
     */
    public function checkRateLimit(): bool
    {
        // Check minute limit
        $minuteCount = $this->usageLogs()
            ->where('created_at', '>=', now()->subMinute())
            ->count();
        
        if ($minuteCount >= $this->rate_limit_per_minute) {
            return false;
        }

        // Check daily limit
        $dailyCount = $this->usageLogs()
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($dailyCount >= $this->rate_limit_per_day) {
            return false;
        }

        return true;
    }
}



