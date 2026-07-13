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
        'default_labour_budget',
        'default_input_budget',
        'default_irrigation_budget',
        'default_overhead_budget',
    ];

    protected $casts = [
        'days_to_maturity' => 'integer',
        'expected_yield_per_acre' => 'decimal:2',
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

    public function nurseryBatches(): HasMany
    {
        return $this->hasMany(NurseryBatch::class);
    }
}
