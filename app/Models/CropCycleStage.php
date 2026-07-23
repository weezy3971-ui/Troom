<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A growth stage within a template, expressed as day offsets from planting.
 * e.g. "Fruiting, day 55-75".
 */
class CropCycleStage extends Model
{
    protected $fillable = [
        'crop_cycle_template_id',
        'stage_name',
        'start_day_offset',
        'end_day_offset',
        'sort_order',
    ];

    protected $casts = [
        'start_day_offset' => 'integer',
        'end_day_offset' => 'integer',
        'sort_order' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(CropCycleTemplate::class, 'crop_cycle_template_id');
    }

    public function schedulePoints(): HasMany
    {
        return $this->hasMany(CropCycleSchedulePoint::class)->orderBy('day_offset');
    }

    public function dayRange(): string
    {
        return 'day ' . $this->start_day_offset . '–' . $this->end_day_offset;
    }
}
