<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CropCycle extends Model
{
    protected $fillable = [
        'block_id',
        'crop_id',
        'season_name',
        'planting_date',
        'expected_harvest_date',
        'status',
    ];

    protected $casts = [
        'planting_date' => 'date',
        'expected_harvest_date' => 'date',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function seasonalBudget(): HasOne
    {
        return $this->hasOne(SeasonalBudget::class);
    }

    public function plantings(): HasMany
    {
        return $this->hasMany(Planting::class);
    }

    public function fertigationLogs(): HasMany
    {
        return $this->hasMany(FertigationLog::class);
    }

    public function sprayLogs(): HasMany
    {
        return $this->hasMany(SprayLog::class);
    }

    public function costAllocations(): HasMany
    {
        return $this->hasMany(CostAllocation::class);
    }

    public function harvestBatches(): HasMany
    {
        return $this->hasMany(HarvestBatch::class);
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function labourAttendances(): HasMany
    {
        return $this->hasMany(LabourAttendance::class);
    }

    /**
     * Total cost booked against this cycle across all allocation sources.
     */
    public function actualCost(): float
    {
        return (float) $this->costAllocations()->sum('amount');
    }

    /**
     * Business rule: a budget_exceeded alert fires when a crop cycle's actual
     * cost surpasses its seasonal budget.
     */
    public function isBudgetExceeded(): bool
    {
        $budget = optional($this->seasonalBudget)->total_budget;

        return $budget > 0 && $this->actualCost() > (float) $budget;
    }

    /**
     * A pre-harvest interval is active when any spray on this cycle has a PHI
     * window that has not yet cleared. Harvest Batches cannot be created while
     * this returns true (enforced in the Harvest module).
     */
    public function hasActivePreHarvestInterval(): bool
    {
        return $this->sprayLogs()
            ->whereRaw("date(log_date, '+' || pre_harvest_interval_days || ' days') >= date('now')")
            ->exists();
    }

    /**
     * A crop cycle cannot move to active until its budget is set.
     */
    public function canActivate(): bool
    {
        return $this->seasonalBudget !== null
            && $this->seasonalBudget->total_budget > 0;
    }

    /**
     * A block can only have one active crop cycle at a time.
     */
    public function blockHasActiveCycle(): bool
    {
        return self::where('block_id', $this->block_id)
            ->where('status', 'active')
            ->where('id', '!=', $this->id ?? 0)
            ->exists();
    }
}
