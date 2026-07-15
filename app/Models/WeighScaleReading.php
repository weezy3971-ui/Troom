<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeighScaleReading extends Model
{
    protected $fillable = [
        'device_name',
        'external_id',
        'weighed_by_worker_id',
        'weighed_by_name',
        'item',
        'weight',
        'unit',
        'weighed_at',
        'block_id',
        'crop_cycle_id',
        'source',
        'notes',
        'acknowledged_at',
        'acknowledged_by',
    ];

    protected $casts = [
        'weight' => 'decimal:3',
        'weighed_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function weighedByWorker(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'weighed_by_worker_id');
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    /** New readings that haven't been reviewed yet. */
    public function scopeUnacknowledged(Builder $query): Builder
    {
        return $query->whereNull('acknowledged_at');
    }
}
