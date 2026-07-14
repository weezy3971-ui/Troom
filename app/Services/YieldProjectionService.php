<?php

namespace App\Services;

use App\Models\CropCycle;

/**
 * Turns a crop cycle's planting data into a projected yield (kg) and projected
 * revenue, refined by germination and plant-population assumptions.
 *
 * Phase 1 skeleton: germination and population come from the crop's default
 * assumptions. Later phases replace these with real germination checks and
 * stand counts recorded through the cycle (see the projection plan).
 *
 * Every accessor degrades gracefully to null/0 when its inputs are missing, so
 * a freshly-planned cycle shows "insufficient data" rather than a divide error.
 */
class YieldProjectionService
{
    /** Fallback germination rate when neither the cycle nor the crop specifies one. */
    private const DEFAULT_GERMINATION_RATE = 0.85;

    public function __construct(private readonly CropCycle $cropCycle)
    {
        $this->cropCycle->loadMissing('crop', 'block', 'plantings', 'harvestBatches');
    }

    /** Total beds planted across this cycle's plantings (null if none recorded). */
    public function plantedBeds(): ?int
    {
        $beds = (int) $this->cropCycle->plantings->sum('bed_count');

        return $beds > 0 ? $beds : null;
    }

    /**
     * Planted area in acres from the cycle's plantings (null if none recorded).
     *
     * Deliberately NOT falling back to the whole block: projecting off the full
     * block area when nothing has been planted overstates a planned cycle wildly.
     * A projection needs real planting detail (beds or an explicit planting area).
     */
    public function plantedArea(): ?float
    {
        $area = (float) $this->cropCycle->plantings->sum('area_acres');

        return $area > 0 ? $area : null;
    }

    /** Assumed germination rate as a fraction (crop default, else house default). */
    public function germinationRate(): float
    {
        $rate = (float) ($this->cropCycle->crop->expected_germination_rate ?? 0);

        return $rate > 0 ? $rate : self::DEFAULT_GERMINATION_RATE;
    }

    /**
     * Surviving plant population as a fraction. Phase 1 assumes a full stand;
     * later phases derive this from the latest recorded stand count.
     */
    public function populationRate(): float
    {
        return 1.0;
    }

    /**
     * Which basis the projection used: 'per_bed', 'per_acre', or null when there
     * isn't enough crop/planting data to project at all.
     */
    public function basis(): ?string
    {
        $crop = $this->cropCycle->crop;

        if ($this->plantedBeds() !== null && (float) $crop->expected_yield_per_bed_kg > 0) {
            return 'per_bed';
        }

        if ($this->plantedArea() !== null && (float) $crop->expected_yield_per_acre > 0) {
            return 'per_acre';
        }

        return null;
    }

    /**
     * Projected saleable yield in kg, discounted by germination and population.
     * Null when the crop/planting data can't support a projection.
     */
    public function projectedYieldKg(): ?float
    {
        $crop = $this->cropCycle->crop;
        $factor = $this->germinationRate() * $this->populationRate();

        return match ($this->basis()) {
            'per_bed'  => round($this->plantedBeds() * (float) $crop->expected_yield_per_bed_kg * $factor, 2),
            'per_acre' => round($this->plantedArea() * (float) $crop->expected_yield_per_acre * $factor, 2),
            default    => null,
        };
    }

    /** Projected revenue = projected kg × the crop's reference price. Null if either is missing. */
    public function projectedRevenue(): ?float
    {
        $kg = $this->projectedYieldKg();
        $price = (float) ($this->cropCycle->crop->reference_price_per_kg ?? 0);

        if ($kg === null || $price <= 0) {
            return null;
        }

        return round($kg * $price, 2);
    }

    /** Actual harvested weight booked against this cycle so far. */
    public function actualYieldKg(): float
    {
        return (float) $this->cropCycle->harvestBatches->sum('quantity_kg');
    }

    /** Actual revenue realised, valued at the crop's reference price (for like-for-like variance). */
    public function actualRevenue(): ?float
    {
        $price = (float) ($this->cropCycle->crop->reference_price_per_kg ?? 0);

        return $price > 0 ? round($this->actualYieldKg() * $price, 2) : null;
    }

    /**
     * Projected-vs-actual yield variance as a fraction of the projection
     * (positive = ahead of projection). Null until both figures exist.
     */
    public function yieldVariance(): ?float
    {
        $projected = $this->projectedYieldKg();
        $actual = $this->actualYieldKg();

        if ($projected === null || $projected <= 0 || $actual <= 0) {
            return null;
        }

        return round(($actual - $projected) / $projected, 4);
    }

    public function hasProjection(): bool
    {
        return $this->projectedYieldKg() !== null;
    }

    /** Everything the view needs in one call. */
    public function summary(): array
    {
        return [
            'basis'             => $this->basis(),
            'planted_beds'      => $this->plantedBeds(),
            'planted_area'      => $this->plantedArea(),
            'germination_rate'  => $this->germinationRate(),
            'population_rate'   => $this->populationRate(),
            'projected_yield'   => $this->projectedYieldKg(),
            'projected_revenue' => $this->projectedRevenue(),
            'actual_yield'      => $this->actualYieldKg(),
            'actual_revenue'    => $this->actualRevenue(),
            'yield_variance'    => $this->yieldVariance(),
            'price_per_kg'      => (float) ($this->cropCycle->crop->reference_price_per_kg ?? 0),
        ];
    }
}
