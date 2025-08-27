<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sms_service_id',
        'order_id',
        'phone_number',
        'country',
        'service',
        'cost',
        'status',
        'sms_code',
        'expires_at',
        'received_at',
        'metadata',
        'provider_order_id'
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'expires_at' => 'datetime',
        'received_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Order Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';
    const STATUS_FAILED = 'failed';

    // Popular Services
    const SERVICE_GOOGLE = 'google';
    const SERVICE_FACEBOOK = 'facebook';
    const SERVICE_TELEGRAM = 'telegram';
    const SERVICE_WHATSAPP = 'whatsapp';
    const SERVICE_TWITTER = 'twitter';
    const SERVICE_INSTAGRAM = 'instagram';
    const SERVICE_UBER = 'uber';
    const SERVICE_GRAB = 'grab';
    const SERVICE_GOJEK = 'gojek';
    const SERVICE_AMAZON = 'amazon';
    const SERVICE_APPLE = 'apple';
    const SERVICE_MICROSOFT = 'microsoft';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function smsService(): BelongsTo
    {
        return $this->belongsTo(SmsService::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByService($query, $service)
    {
        return $query->where('service', $service);
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function hasSmsCode(): bool
    {
        return !empty($this->sms_code);
    }

    public function markAsCompleted(string $smsCode): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'sms_code' => $smsCode,
            'received_at' => now()
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    public function markAsCancelled(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    public function getFormattedPhoneNumber(): string
    {
        return '+' . $this->phone_number;
    }

    public function getServiceDisplayName(): string
    {
        $serviceNames = [
            self::SERVICE_GOOGLE => 'Google',
            self::SERVICE_FACEBOOK => 'Facebook',
            self::SERVICE_TELEGRAM => 'Telegram',
            self::SERVICE_WHATSAPP => 'WhatsApp',
            self::SERVICE_TWITTER => 'Twitter',
            self::SERVICE_INSTAGRAM => 'Instagram',
            self::SERVICE_UBER => 'Uber',
            self::SERVICE_GRAB => 'Grab',
            self::SERVICE_GOJEK => 'GoJek',
            self::SERVICE_AMAZON => 'Amazon',
            self::SERVICE_APPLE => 'Apple',
            self::SERVICE_MICROSOFT => 'Microsoft'
        ];

        return $serviceNames[$this->service] ?? ucfirst($this->service);
    }

    public function getStatusLabel(): string
    {
        $statusLabels = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_FAILED => 'Failed'
        ];

        return $statusLabels[$this->status] ?? 'Unknown';
    }
}
