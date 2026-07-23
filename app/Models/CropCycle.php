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

    /**
     * The land preparation this planting followed on from. Linking it is what
     * pulls prep spend into the cycle's cost rather than leaving it stranded on
     * the block with no stage to belong to.
     */
    public function landPreparation(): HasOne
    {
        return $this->hasOne(LandPreparation::class);
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

    public function germinationChecks(): HasMany
    {
        return $this->hasMany(GerminationCheck::class);
    }

    public function plantPopulationCounts(): HasMany
    {
        return $this->hasMany(PlantPopulationCount::class);
    }

    public function yieldForecasts(): HasMany
    {
        return $this->hasMany(YieldForecast::class);
    }

    /**
     * Days from today until the expected harvest — negative once that date has
     * passed. Null when the cycle has no expected harvest date, which is the
     * honest answer rather than pretending it is due today.
     */
    public function daysToHarvest(): ?int
    {
        if (! $this->expected_harvest_date) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expected_harvest_date, false);
    }

    /**
     * How far through planting → expected harvest this cycle is, 0–100.
     * Null unless both dates are set, since progress is meaningless without a
     * span to measure against.
     */
    public function progressPercent(): ?int
    {
        if (! $this->planting_date || ! $this->expected_harvest_date) {
            return null;
        }

        $span = $this->planting_date->diffInDays($this->expected_harvest_date);

        if ($span <= 0) {
            return 100;
        }

        $elapsed = $this->planting_date->diffInDays(now()->startOfDay(), false);

        return (int) max(0, min(100, round($elapsed / $span * 100)));
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
     *
     * NOTE: Uses a collection filter rather than raw SQL so it works across
     * all database engines (SQLite, MySQL, PostgreSQL).
     */
    public function hasActivePreHarvestInterval(): bool
    {
        return $this->sprayLogs
            ->filter(fn($spray) => $spray->isPhiActive())
            ->isNotEmpty();
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
     * Land preparation must be finished before a block is planted into.
     *
     * A cycle with no preparation round attached passes: rounds are attached
     * when a cycle is created, so the only cycles without one pre-date the
     * rule, and blocking those retrospectively would strand real plantings.
     */
    public function landPrepSatisfied(): bool
    {
        $prep = $this->landPreparation;

        return $prep === null || $prep->isSatisfied();
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
