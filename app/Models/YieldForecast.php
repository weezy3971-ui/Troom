<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YieldForecast extends Model
{
    protected $fillable = [
        'crop_cycle_id',
        'forecast_date',
        'sample_bed_count',
        'total_bed_count',
        'sample_yield_kg',
        'projected_total_kg',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'sample_bed_count' => 'integer',
        'total_bed_count' => 'integer',
        'sample_yield_kg' => 'decimal:2',
        'projected_total_kg' => 'decimal:2',
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
