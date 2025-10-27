<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'charges',
        'actual_amount',
        'credit_amount',
        'reference',
        'status',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'charges' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who made this deposit
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who processed this deposit (from metadata)
     */
    public function getProcessedByAttribute()
    {
        if (is_array($this->metadata) && isset($this->metadata['processed_by'])) {
            return User::find($this->metadata['processed_by']);
        }
        return null;
    }

    /**
     * Get admin note from metadata
     */
    public function getAdminNoteAttribute()
    {
        if (is_array($this->metadata) && isset($this->metadata['admin_note'])) {
            return $this->metadata['admin_note'];
        }
        return null;
    }

    /**
     * Get processed at timestamp from metadata
     */
    public function getProcessedAtAttribute()
    {
        if (is_array($this->metadata) && isset($this->metadata['processed_at'])) {
            return $this->metadata['processed_at'];
        }
        return null;
    }
}
