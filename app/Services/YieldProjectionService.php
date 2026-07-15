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
        $this->cropCycle->loadMissing(
            'crop', 'block', 'plantings', 'harvestBatches',
            'germinationChecks', 'plantPopulationCounts', 'yieldForecasts'
        );
    }

    /** Most recent germination check for this cycle, if any. */
    public function latestGerminationCheck()
    {
        return $this->cropCycle->germinationChecks->sortByDesc('check_date')->first();
    }

    /** Most recent plant-population (stand) count for this cycle, if any. */
    public function latestPopulationCount()
    {
        return $this->cropCycle->plantPopulationCounts->sortByDesc('count_date')->first();
    }

    /** Most recent pre-harvest yield forecast for this cycle, if any. */
    public function latestForecast()
    {
        return $this->cropCycle->yieldForecasts->sortByDesc('forecast_date')->first();
    }

    /** 'measured' when a real germination reading exists, else 'assumed'. */
    public function germinationSource(): string
    {
        return $this->latestGerminationCheck() ? 'measured' : 'assumed';
    }

    /** 'measured' when a real stand count exists, else 'assumed'. */
    public function populationSource(): string
    {
        return $this->latestPopulationCount() ? 'measured' : 'assumed';
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

    /**
     * Germination rate as a fraction. Uses the latest field reading when one
     * exists; otherwise falls back to the crop default, then the house default.
     */
    public function germinationRate(): float
    {
        if ($check = $this->latestGerminationCheck()) {
            return (float) $check->germination_rate;
        }

        $rate = (float) ($this->cropCycle->crop->expected_germination_rate ?? 0);

        return $rate > 0 ? $rate : self::DEFAULT_GERMINATION_RATE;
    }

    /**
     * Surviving plant population as a fraction, from the latest stand count.
     * Defaults to a full stand (1.0) until a count is recorded.
     */
    public function populationRate(): float
    {
        if ($count = $this->latestPopulationCount()) {
            return (float) $count->population_rate;
        }

        return 1.0;
    }

    /**
     * Pre-harvest sample forecast in kg (sample yield/bed × total beds), from the
     * latest recorded sampling walk. Null until a forecast is recorded.
     */
    public function preHarvestForecastKg(): ?float
    {
        $forecast = $this->latestForecast();

        return $forecast ? (float) $forecast->projected_total_kg : null;
    }

    /**
     * Actual-vs-forecast variance as a fraction of the forecast
     * (positive = beat the forecast). Null until both figures exist.
     */
    public function forecastVariance(): ?float
    {
        $forecast = $this->preHarvestForecastKg();
        $actual = $this->actualYieldKg();

        if ($forecast === null || $forecast <= 0 || $actual <= 0) {
            return null;
        }

        return round(($actual - $forecast) / $forecast, 4);
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
            'basis'                => $this->basis(),
            'planted_beds'         => $this->plantedBeds(),
            'planted_area'         => $this->plantedArea(),
            'germination_rate'     => $this->germinationRate(),
            'germination_source'   => $this->germinationSource(),
            'population_rate'      => $this->populationRate(),
            'population_source'    => $this->populationSource(),
            'projected_yield'      => $this->projectedYieldKg(),
            'projected_revenue'    => $this->projectedRevenue(),
            'pre_harvest_forecast' => $this->preHarvestForecastKg(),
            'forecast_variance'    => $this->forecastVariance(),
            'actual_yield'         => $this->actualYieldKg(),
            'actual_revenue'       => $this->actualRevenue(),
            'yield_variance'       => $this->yieldVariance(),
            'price_per_kg'         => (float) ($this->cropCycle->crop->reference_price_per_kg ?? 0),
        ];
    }
}
