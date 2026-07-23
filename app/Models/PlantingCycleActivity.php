<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What was actually done against a cycle. A row carrying a schedule point is
 * the completion of scheduled work; a null schedule point is an ad hoc,
 * unscheduled activity.
 */
class PlantingCycleActivity extends Model
{
    protected $fillable = [
        'crop_cycle_id',
        'crop_cycle_schedule_point_id',
        'activity_type',
        'product_name',
        'performed_date',
        'performed_by',
        'dosage',
        'cost_kes',
        'notes',
    ];

    protected $casts = [
        'performed_date' => 'date',
        'cost_kes' => 'decimal:2',
    ];

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }

    public function schedulePoint(): BelongsTo
    {
        return $this->belongsTo(CropCycleSchedulePoint::class, 'crop_cycle_schedule_point_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function isScheduled(): bool
    {
        return $this->crop_cycle_schedule_point_id !== null;
    }

    /**
     * Days between the scheduled day and the day the work was logged. Negative
     * is early, positive is late, null when there was nothing scheduled.
     */
    public function daysLate(): ?int
    {
        if (! $this->isScheduled() || ! $this->cropCycle?->planting_date) {
            return null;
        }

        $due = $this->cropCycle->planting_date->copy()
            ->addDays($this->schedulePoint->day_offset);

        return $due->diffInDays($this->performed_date, false);
    }

    public function activityLabel(): string
    {
        return CropCycleSchedulePoint::ACTIVITY_TYPES[$this->activity_type] ?? $this->activity_type;
    }
}
