<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The reusable planting-to-harvest plan. Defines both the growth stages and
 * exactly which chemical or input to apply, and when. Selected when a block
 * starts a new cycle.
 */
class CropCycleTemplate extends Model
{
    protected $fillable = [
        'crop_id',
        'crop_name',
        'variety',
        'total_cycle_days',
        'description',
        'is_active',
    ];

    protected $casts = [
        'total_cycle_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(CropCycleStage::class)
            ->orderBy('sort_order')
            ->orderBy('start_day_offset');
    }

    public function schedulePoints(): HasMany
    {
        return $this->hasMany(CropCycleSchedulePoint::class)->orderBy('day_offset');
    }

    public function cropCycles(): HasMany
    {
        return $this->hasMany(CropCycle::class);
    }

    public function label(): string
    {
        return trim($this->crop_name . ' (' . ($this->variety ?: 'any variety') . ')')
            . ' — ' . $this->total_cycle_days . ' days';
    }

    /**
     * The stage covering a given day offset, if any.
     */
    public function stageForDay(int $day): ?CropCycleStage
    {
        return $this->stages
            ->first(fn (CropCycleStage $s) => $day >= $s->start_day_offset && $day <= $s->end_day_offset);
    }
}
