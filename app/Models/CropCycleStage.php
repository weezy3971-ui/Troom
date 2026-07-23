<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CropCycleStage extends Model
{
    protected $fillable = [
        'crop_cycle_id',
        'crop_program_stage_id',
        'sequence',
        'name',
        'activity_type',
        'due_date',
        'status',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }

    public function programStage(): BelongsTo
    {
        return $this->belongsTo(CropProgramStage::class, 'crop_program_stage_id');
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /** Pending and due on or before today. */
    public function isDue(): bool
    {
        return $this->isPending() && $this->due_date->isToday();
    }

    /** Pending and past its due date. */
    public function isOverdue(): bool
    {
        return $this->isPending() && $this->due_date->isPast() && ! $this->due_date->isToday();
    }

    /** Pending stages due within the next $days days (inclusive of overdue). */
    public function scopeDueSoon(Builder $query, int $days = 2): Builder
    {
        return $query->where('status', 'pending')
            ->whereDate('due_date', '<=', now()->addDays($days)->toDateString());
    }
}
