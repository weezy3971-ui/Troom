<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementRequest extends Model
{
    protected $fillable = [
        'farm_id',
        'crop_cycle_id',
        'requested_by',
        'needed_by',
        'status',
        'ordered_at',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'needed_by' => 'date',
        'ordered_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ProcurementRequestLine::class);
    }

    public function isReceived(): bool
    {
        return $this->status === 'received';
    }

    public function estimatedTotal(): float
    {
        return (float) $this->lines->sum(fn ($line) => (float) $line->estimated_cost);
    }

    /** Requested/ordered but not received by its needed-by date. */
    public function isOverdue(): bool
    {
        return ! $this->isReceived()
            && $this->needed_by !== null
            && $this->needed_by->isPast();
    }
}
