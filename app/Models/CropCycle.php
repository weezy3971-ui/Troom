<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A block running a crop cycle template — the spec's planting cycle. The table
 * keeps the crop_cycles name because nineteen other tables hold crop_cycle_id
 * foreign keys into it.
 */
class CropCycle extends Model
{
    protected $fillable = [
        'block_id',
        'crop_id',
        'crop_cycle_template_id',
        'season_name',
        'planting_date',
        'expected_harvest_date',
        'actual_end_date',
        'current_stage_id',
        'status',
    ];

    protected $casts = [
        'planting_date' => 'date',
        'expected_harvest_date' => 'date',
        'actual_end_date' => 'date',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CropCycleTemplate::class, 'crop_cycle_template_id');
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(CropCycleStage::class, 'current_stage_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(PlantingCycleActivity::class)->orderByDesc('performed_date');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
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
     * A block can only have one active crop cycle at a time.
     */
    public function blockHasActiveCycle(): bool
    {
        return self::where('block_id', $this->block_id)
            ->where('status', 'active')
            ->where('id', '!=', $this->id ?? 0)
            ->exists();
    }

    /**
     * How many days into the cycle today is. Day 0 is the planting date.
     * Null when the cycle has no planting date to count from.
     */
    public function dayOffset(?\DateTimeInterface $on = null): ?int
    {
        if (! $this->planting_date) {
            return null;
        }

        $on = $on ? \Illuminate\Support\Carbon::instance($on) : now();

        return (int) $this->planting_date->startOfDay()->diffInDays($on->startOfDay(), false);
    }

    /**
     * The calendar date a schedule point falls due for this cycle.
     */
    public function dueDateFor(CropCycleSchedulePoint $point): ?\Illuminate\Support\Carbon
    {
        return $this->planting_date?->copy()->addDays($point->day_offset);
    }

    /**
     * The template stage covering today, or null if the cycle has run past the
     * end of its template.
     */
    public function stageToday(): ?CropCycleStage
    {
        $day = $this->dayOffset();

        return $day === null ? null : $this->template?->stageForDay($day);
    }

    /**
     * The whole template schedule resolved onto this cycle's calendar, each
     * point carrying its due date and whether it has been logged. This is what
     * the cycle detail page renders and what spray-compliance reporting reads.
     *
     * @return \Illuminate\Support\Collection<int, array{point: CropCycleSchedulePoint, due_date: ?\Illuminate\Support\Carbon, activity: ?PlantingCycleActivity, status: string}>
     */
    public function resolvedSchedule(): \Illuminate\Support\Collection
    {
        if (! $this->template) {
            return collect();
        }

        $logged = $this->activities->keyBy('crop_cycle_schedule_point_id');

        return $this->template->schedulePoints->map(function (CropCycleSchedulePoint $point) use ($logged) {
            $due = $this->dueDateFor($point);
            $activity = $logged->get($point->id);

            $status = match (true) {
                $activity !== null => 'done',
                $due === null => 'unscheduled',
                $due->isFuture() => 'upcoming',
                $due->isToday() => 'due',
                default => 'overdue',
            };

            return [
                'point' => $point,
                'due_date' => $due,
                'activity' => $activity,
                'status' => $status,
            ];
        });
    }

    /**
     * Share of schedule points completed on or before their due date, over the
     * points that have come due. Null while nothing has come due yet.
     */
    public function sprayComplianceRate(): ?float
    {
        $due = $this->resolvedSchedule()->reject(fn ($row) => $row['status'] === 'upcoming');

        if ($due->isEmpty()) {
            return null;
        }

        $onTime = $due->filter(
            fn ($row) => $row['activity'] !== null
                && $row['due_date'] !== null
                && $row['activity']->performed_date->lessThanOrEqualTo($row['due_date'])
        );

        return round($onTime->count() / $due->count(), 4);
    }
}
