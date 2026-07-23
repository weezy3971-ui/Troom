<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Crop extends Model
{
    protected $fillable = [
        'name',
        'variety',
        'crop_type',
        'days_to_maturity',
        'expected_yield_per_acre',
        'seeds_per_bed',
        'expected_yield_per_bed_kg',
        'reference_price_per_kg',
        'expected_germination_rate',
        'default_labour_budget',
        'default_input_budget',
        'default_irrigation_budget',
        'default_overhead_budget',
    ];

    protected $casts = [
        'days_to_maturity' => 'integer',
        'expected_yield_per_acre' => 'decimal:2',
        'seeds_per_bed' => 'integer',
        'expected_yield_per_bed_kg' => 'decimal:2',
        'reference_price_per_kg' => 'decimal:2',
        'expected_germination_rate' => 'decimal:3',
        'default_labour_budget' => 'decimal:2',
        'default_input_budget' => 'decimal:2',
        'default_irrigation_budget' => 'decimal:2',
        'default_overhead_budget' => 'decimal:2',
    ];

    /**
     * Sum of the crop's default budget template (0 if none set).
     */
    public function defaultTotalBudget(): float
    {
        return (float) $this->default_labour_budget
            + (float) $this->default_input_budget
            + (float) $this->default_irrigation_budget
            + (float) $this->default_overhead_budget;
    }

    /**
     * Whether this crop carries a usable budget template.
     */
    public function hasBudgetTemplate(): bool
    {
        return $this->defaultTotalBudget() > 0;
    }

    public function cropCycles(): HasMany
    {
        return $this->hasMany(CropCycle::class);
    }

    public function cycleTemplates(): HasMany
    {
        return $this->hasMany(CropCycleTemplate::class);
    }

    /** The template offered by default when starting a cycle on this crop. */
    public function defaultTemplate(): ?CropCycleTemplate
    {
        return $this->cycleTemplates()
            ->where('is_active', true)
            ->with('stages', 'schedulePoints')
            ->first();
    }

    public function nurseryBatches(): HasMany
    {
        return $this->hasMany(NurseryBatch::class);
    }
}
