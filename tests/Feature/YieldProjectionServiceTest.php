<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\HarvestBatch;
use App\Models\NurseryBatch;
use App\Models\Planting;
use App\Services\YieldProjectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YieldProjectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeCycle(array $cropAttrs): CropCycle
    {
        $farm = Farm::create(['name' => 'Test Farm', 'location' => 'Test', 'size_acres' => 100]);
        $block = Block::create(['farm_id' => $farm->id, 'name' => 'Test Block', 'size_acres' => 5, 'soil_type' => 'loam']);
        $crop = Crop::create(array_merge(['name' => 'Test Crop', 'crop_type' => 'Vegetable'], $cropAttrs));

        return CropCycle::create([
            'block_id' => $block->id,
            'crop_id' => $crop->id,
            'season_name' => 'Test Season',
            'planting_date' => '2026-01-01',
            'status' => 'active',
        ]);
    }

    private function addPlanting(CropCycle $cycle, array $attrs): void
    {
        $nursery = NurseryBatch::create([
            'crop_id' => $cycle->crop_id,
            'sow_date' => '2026-01-01',
            'expected_ready_date' => '2026-01-15',
            'quantity' => 100000,
            'status' => 'transplanted',
        ]);

        Planting::create(array_merge([
            'nursery_batch_id' => $nursery->id,
            'crop_cycle_id' => $cycle->id,
            'quantity' => 1000,
            'planting_date' => '2026-01-01',
        ], $attrs));
    }

    public function test_per_bed_projection_discounts_by_germination(): void
    {
        $cycle = $this->makeCycle([
            'expected_yield_per_bed_kg' => 7,
            'reference_price_per_kg' => 120,
            'expected_germination_rate' => 0.85,
        ]);
        $this->addPlanting($cycle, ['bed_count' => 90]);

        $service = new YieldProjectionService($cycle->fresh());

        $this->assertSame('per_bed', $service->basis());
        $this->assertSame(90, $service->plantedBeds());
        // 90 beds × 7 kg × 0.85 germination
        $this->assertEqualsWithDelta(535.5, $service->projectedYieldKg(), 0.01);
        // × KES 120/kg
        $this->assertEqualsWithDelta(64260.0, $service->projectedRevenue(), 0.01);
    }

    public function test_variance_compares_actual_harvest_to_projection(): void
    {
        $cycle = $this->makeCycle([
            'expected_yield_per_bed_kg' => 7,
            'reference_price_per_kg' => 120,
            'expected_germination_rate' => 0.85,
        ]);
        $this->addPlanting($cycle, ['bed_count' => 90]);
        HarvestBatch::create([
            'crop_cycle_id' => $cycle->id,
            'block_id' => $cycle->block_id,
            'harvest_date' => '2026-03-01',
            'quantity_kg' => 650,
            'rejects_kg' => 0,
        ]);

        $service = new YieldProjectionService($cycle->fresh());

        $this->assertEqualsWithDelta(650.0, $service->actualYieldKg(), 0.01);
        // (650 - 535.5) / 535.5 ≈ 0.2138
        $this->assertEqualsWithDelta(0.2138, $service->yieldVariance(), 0.001);
    }

    public function test_per_acre_basis_used_when_no_bed_yield_defined(): void
    {
        $cycle = $this->makeCycle([
            'expected_yield_per_acre' => 4000,
            'expected_germination_rate' => 0.90,
        ]);
        $this->addPlanting($cycle, ['area_acres' => 2]);

        $service = new YieldProjectionService($cycle->fresh());

        $this->assertSame('per_acre', $service->basis());
        // 2 acres × 4000 kg × 0.90
        $this->assertEqualsWithDelta(7200.0, $service->projectedYieldKg(), 0.01);
        // No reference price → no revenue projection
        $this->assertNull($service->projectedRevenue());
    }

    public function test_no_projection_without_planting_detail(): void
    {
        $cycle = $this->makeCycle(['expected_yield_per_bed_kg' => 7]);

        $service = new YieldProjectionService($cycle->fresh());

        $this->assertNull($service->basis());
        $this->assertNull($service->projectedYieldKg());
        $this->assertFalse($service->hasProjection());
    }

    public function test_does_not_fall_back_to_full_block_area(): void
    {
        // Crop can project per-acre, but with no planting recorded the service must
        // NOT use the block's own 5-acre size — that would overstate a planned cycle.
        $cycle = $this->makeCycle(['expected_yield_per_acre' => 4000]);

        $service = new YieldProjectionService($cycle->fresh());

        $this->assertNull($service->plantedArea());
        $this->assertNull($service->projectedYieldKg());
    }
}
