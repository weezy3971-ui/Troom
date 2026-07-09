<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Block extends Model
{
    protected $fillable = [
        'farm_id',
        'name',
        'size_acres',
        'soil_type',
        'boundary_geojson',
    ];

    protected $casts = [
        'size_acres' => 'decimal:2',
        'boundary_geojson' => 'array',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function cropCycles(): HasMany
    {
        return $this->hasMany(CropCycle::class);
    }

    public function dailyActivities(): HasMany
    {
        return $this->hasMany(DailyActivity::class);
    }

    public function irrigationLogs(): HasMany
    {
        return $this->hasMany(IrrigationLog::class);
    }

    public function labourAttendances(): HasMany
    {
        return $this->hasMany(LabourAttendance::class);
    }

    /**
     * Check if block has an active crop cycle.
     */
    public function hasActiveCropCycle(): bool
    {
        return $this->cropCycles()->where('status', 'active')->exists();
    }
}
