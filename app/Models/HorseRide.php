<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorseRide extends Model
{
    protected $fillable = [
        'receipt_number',
        'customer_name',
        'customer_phone',
        'horse_id',
        'guide_id',
        'start_time',
        'duration_minutes',
        'end_time',
        'amount',
        'payment_status',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_minutes' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function horse(): BelongsTo
    {
        return $this->belongsTo(Horse::class);
    }

    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }

    /**
     * Effective status for display: an assigned ride whose end time has passed
     * reads as completed without needing a background job.
     */
    public function effectiveStatus(): string
    {
        if ($this->status === 'assigned' && $this->end_time->isPast()) {
            return 'completed';
        }

        return $this->status;
    }

    public function isAssigned(): bool
    {
        return $this->horse_id && $this->guide_id;
    }
}
