<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Exactly which chemical or input to apply, and when — the row the reminder
 * engine reads. e.g. day 60, Mancozeb fungicide, "blight prevention".
 */
class CropCycleSchedulePoint extends Model
{
    public const ACTIVITY_TYPES = [
        'spray' => 'Spray',
        'foliar_feed' => 'Foliar feed',
        'input' => 'Input',
        'harvest_check' => 'Harvest check',
    ];

    protected $fillable = [
        'crop_cycle_template_id',
        'crop_cycle_stage_id',
        'day_offset',
        'activity_type',
        'product_name',
        'purpose',
        'dosage',
        'pre_harvest_interval_days',
    ];

    protected $casts = [
        'day_offset' => 'integer',
        'pre_harvest_interval_days' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(CropCycleTemplate::class, 'crop_cycle_template_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(CropCycleStage::class, 'crop_cycle_stage_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(PlantingCycleActivity::class);
    }

    public function activityLabel(): string
    {
        return self::ACTIVITY_TYPES[$this->activity_type] ?? $this->activity_type;
    }

    /**
     * What the reminder says: "Day 60 — spray Mancozeb fungicide (blight
     * prevention)".
     */
    public function description(): string
    {
        $text = 'Day ' . $this->day_offset . ' — ' . strtolower($this->activityLabel());

        if ($this->product_name) {
            $text .= ': ' . $this->product_name;
        }

        if ($this->dosage) {
            $text .= ' @ ' . $this->dosage;
        }

        if ($this->purpose) {
            $text .= ' (' . $this->purpose . ')';
        }

        return $text;
    }
}
