<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantPopulationCount extends Model
{
    protected $fillable = [
        'crop_cycle_id',
        'count_date',
        'days_after_planting',
        'population_rate',
        'sample_bed_count',
        'plants_counted',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'count_date' => 'date',
        'days_after_planting' => 'integer',
        'population_rate' => 'decimal:3',
        'sample_bed_count' => 'integer',
        'plants_counted' => 'integer',
    ];

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
