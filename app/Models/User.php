<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'username',
        'password',
        'balance',
        'wallet_balance',
        'account_number',
        'bank_name',
        'account_name',
        'status',
        'role',
        'referral_code',
        'referred_by',
        'settings',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'balance' => 'decimal:2',
        'wallet_balance' => 'decimal:2',
        'settings' => 'array',
    ];

    /**
     * Get the user who referred this user
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /**
     * Get users referred by this user
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    /**
     * Get user's transactions
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Update user's balance
     */
    public function updateBalance(float $amount, string $type = 'add'): bool
    {
        if ($type === 'add') {
            $this->balance += $amount;
        } elseif ($type === 'subtract') {
            if ($this->balance < $amount) {
                return false; // Insufficient balance
            }
            $this->balance -= $amount;
        }

        return $this->save();
    }

    /**
     * Update user's wallet balance
     */
    public function updateWalletBalance(float $amount, string $type = 'add'): bool
    {
        if ($type === 'add') {
            $this->wallet_balance += $amount;
        } elseif ($type === 'subtract') {
            if ($this->wallet_balance < $amount) {
                return false; // Insufficient wallet balance
            }
            $this->wallet_balance -= $amount;
        }

        return $this->save();
    }

    /**
     * Generate unique referral code
     */
    public function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Update last login information
     */
    public function updateLastLogin(string $ip = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip ?? request()->ip(),
        ]);
    }
}
