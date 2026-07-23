<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Core-platform task. Raised by the reminder engine off a schedule point, or
 * assigned by hand. Drives the field app's "today's tasks" list.
 */
class Task extends Model
{
    protected $fillable = [
        'assigned_to',
        'module',
        'related_type',
        'related_id',
        'description',
        'status',
        'due_date',
        'completed_at',
        'source',
        'crop_cycle_schedule_point_id',
        'crop_cycle_id',
        'escalated_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function schedulePoint(): BelongsTo
    {
        return $this->belongsTo(CropCycleSchedulePoint::class, 'crop_cycle_schedule_point_id');
    }

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isOverdue(): bool
    {
        return $this->isPending()
            && $this->due_date !== null
            && $this->due_date->isPast()
            && ! $this->due_date->isToday();
    }

    /** How far past due, in whole days. Zero when not overdue. */
    public function daysOverdue(): int
    {
        return $this->isOverdue() ? $this->due_date->diffInDays(now()) : 0;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeDueBy(Builder $query, $date): Builder
    {
        return $query->whereDate('due_date', '<=', $date);
    }
}
