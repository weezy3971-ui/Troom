<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\HarvestBatch;
use App\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HarvestAndInventoryTest extends TestCase
{
    use RefreshDatabase;

    private function makeBatch(): HarvestBatch
    {
        $farm = Farm::create(['name' => 'F', 'location' => 'X', 'size_acres' => 10]);
        $block = Block::create(['farm_id' => $farm->id, 'name' => 'B', 'size_acres' => 2, 'soil_type' => 'loam']);
        $crop = Crop::create(['name' => 'French Bean', 'crop_type' => 'Vegetable']);
        $cycle = CropCycle::create([
            'block_id' => $block->id, 'crop_id' => $crop->id, 'season_name' => 'S',
            'planting_date' => '2026-01-01', 'status' => 'active',
        ]);

        return HarvestBatch::create([
            'crop_cycle_id' => $cycle->id, 'block_id' => $block->id,
            'harvest_date' => '2026-03-01', 'quantity_kg' => 500, 'rejects_kg' => 0,
        ]);
    }

    public function test_harvest_batch_confirmation_flag(): void
    {
        $batch = $this->makeBatch();
        $this->assertFalse($batch->isConfirmed());

        $batch->update(['confirmed_by' => null, 'confirmed_at' => now()]);
        $this->assertTrue($batch->fresh()->isConfirmed());
    }

    public function test_harvest_by_products_relation(): void
    {
        $batch = $this->makeBatch();
        $batch->byProducts()->create(['name' => 'Offcut tips', 'quantity_kg' => 35]);
        $batch->byProducts()->create(['name' => 'Trimmed leaves', 'quantity_kg' => 15]);

        $this->assertCount(2, $batch->byProducts);
        $this->assertEqualsWithDelta(50.0, $batch->byProducts->sum('quantity_kg'), 0.01);
    }

    public function test_inventory_stage_label(): void
    {
        $farm = Farm::create(['name' => 'F', 'location' => 'X', 'size_acres' => 10]);
        $item = InventoryItem::create([
            'farm_id' => $farm->id, 'name' => 'NPK', 'category' => 'fertilizer',
            'stage' => 'pre_harvest_input', 'unit' => 'kg', 'reorder_level' => 10,
        ]);

        $this->assertSame('Pre-harvest input', $item->stageLabel());

        $item->update(['stage' => 'nonsense']);
        $this->assertSame('General', $item->fresh()->stageLabel());
    }
}
