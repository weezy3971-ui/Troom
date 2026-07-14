<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabourAttendance extends Model
{
    protected $fillable = [
        'worker_id',
        'attendance_date',
        'worker_name',
        'block_id',
        'crop_cycle_id',
        'task',
        'pay_basis',
        'target_unit',
        'target_qty',
        'qty_completed',
        'rate_per_unit',
        'hours_worked',
        'rate',
        'checked_in_at',
        'checked_out_at',
        'cost',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'hours_worked' => 'decimal:2',
        'rate' => 'decimal:2',
        'target_qty' => 'decimal:2',
        'qty_completed' => 'decimal:2',
        'rate_per_unit' => 'decimal:2',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'cost' => 'decimal:2',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function isTargetBased(): bool
    {
        return $this->pay_basis === 'target';
    }

    /** Hours between check-in and check-out, or null if either is missing. */
    public function hoursFromTimestamps(): ?float
    {
        if (! $this->checked_in_at || ! $this->checked_out_at) {
            return null;
        }

        return round($this->checked_in_at->floatDiffInHours($this->checked_out_at), 2);
    }

    /**
     * Cost of the entry: target/piece-rate is qty × rate_per_unit; otherwise
     * hours × hourly rate.
     */
    public function computeCost(): float
    {
        if ($this->isTargetBased()) {
            return (float) $this->qty_completed * (float) $this->rate_per_unit;
        }

        return (float) $this->hours_worked * (float) $this->rate;
    }
}
