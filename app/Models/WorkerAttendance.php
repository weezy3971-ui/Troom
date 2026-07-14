<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerAttendance extends Model
{
    protected $fillable = [
        'worker_id',
        'work_date',
        'checked_in_at',
        'checked_out_at',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'work_date' => 'date',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** Currently on site — checked in but not yet checked out. */
    public function scopeOnSite(Builder $query): Builder
    {
        return $query->whereNull('checked_out_at');
    }

    public function isCheckedOut(): bool
    {
        return $this->checked_out_at !== null;
    }

    /** Hours on site, once checked out. */
    public function hoursWorked(): ?float
    {
        if (! $this->isCheckedOut()) {
            return null;
        }

        return round($this->checked_in_at->floatDiffInHours($this->checked_out_at), 2);
    }
}
