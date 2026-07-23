<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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

    /** Rounds of land preparation done on this block, newest first. */
    public function landPreparations(): HasMany
    {
        return $this->hasMany(LandPreparation::class)->latest('id');
    }

    /** The preparation round a new crop cycle on this block would follow on from. */
    public function latestLandPreparation(): ?LandPreparation
    {
        return $this->landPreparations()->with('tasks')->first();
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

    public function harvestBatches(): HasMany
    {
        return $this->hasMany(HarvestBatch::class);
    }

    /**
     * Fertigation logs recorded against any crop cycle on this block.
     */
    public function fertigationLogs(): HasManyThrough
    {
        return $this->hasManyThrough(FertigationLog::class, CropCycle::class);
    }

    /**
     * Spray logs recorded against any crop cycle on this block.
     */
    public function sprayLogs(): HasManyThrough
    {
        return $this->hasManyThrough(SprayLog::class, CropCycle::class);
    }

    /**
     * Check if block has an active crop cycle.
     */
    public function hasActiveCropCycle(): bool
    {
        return $this->cropCycles()->where('status', 'active')->exists();
    }
}
