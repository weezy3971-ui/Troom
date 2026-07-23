<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\SeasonalBudget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CropCycleOverviewTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): User
    {
        return User::factory()->create(['role' => 'horticulture_manager', 'is_active' => true]);
    }

    private function cycle(array $attrs = []): CropCycle
    {
        $farm = Farm::create(['name' => 'Trooms Naivasha', 'location' => 'Naivasha', 'size_acres' => 40]);
        $block = Block::create(['farm_id' => $farm->id, 'name' => 'Block C', 'size_acres' => 2.5]);
        $crop = Crop::create(['name' => 'French Bean', 'crop_type' => 'Vegetable', 'variety' => 'Serengeti']);

        return CropCycle::create(array_merge([
            'block_id' => $block->id,
            'crop_id' => $crop->id,
            'season_name' => 'Long Rains 2026',
            'planting_date' => now()->subDays(20)->toDateString(),
            'expected_harvest_date' => now()->addDays(10)->toDateString(),
            'status' => 'active',
        ], $attrs));
    }

    public function test_the_crop_page_shows_the_dates_that_define_a_cycle(): void
    {
        $cycle = $this->cycle();

        // Per the module spec, a crop cycle is block, crop, season, planting
        // date, expected harvest date and status — the dates were missing.
        $this->actingAs($this->manager())
            ->get(route('crops.show', $cycle->crop))
            ->assertOk()
            ->assertSee('Long Rains 2026')
            ->assertSee('Block C')
            ->assertSee($cycle->planting_date->format('M d, Y'))
            ->assertSee($cycle->expected_harvest_date->format('M d, Y'))
            ->assertSee('10 days to go');
    }

    public function test_the_crop_page_flags_an_unbudgeted_planned_cycle(): void
    {
        $cycle = $this->cycle(['status' => 'planned']);

        // A cycle cannot go active until its budget is set, so the crop page
        // says so rather than leaving a blank cell.
        $this->actingAs($this->manager())
            ->get(route('crops.show', $cycle->crop))
            ->assertOk()
            ->assertSee('Not budgeted')
            ->assertSee('needed before activation');
    }

    public function test_the_crop_page_shows_budget_against_actual_spend(): void
    {
        $cycle = $this->cycle();
        SeasonalBudget::create([
            'crop_cycle_id' => $cycle->id,
            'labour_budget' => 10000, 'input_budget' => 5000,
            'irrigation_budget' => 0, 'overhead_budget' => 0, 'total_budget' => 15000,
        ]);

        $this->actingAs($this->manager())
            ->get(route('crops.show', $cycle->crop))
            ->assertOk()
            ->assertSee('15,000');
    }

    public function test_progress_and_days_to_harvest_are_null_without_both_dates(): void
    {
        $cycle = $this->cycle(['planting_date' => null, 'expected_harvest_date' => null]);

        $this->assertNull($cycle->progressPercent());
        $this->assertNull($cycle->daysToHarvest());
    }

    public function test_progress_is_measured_between_planting_and_expected_harvest(): void
    {
        $cycle = $this->cycle([
            'planting_date' => now()->subDays(25)->toDateString(),
            'expected_harvest_date' => now()->addDays(25)->toDateString(),
        ]);

        $this->assertSame(50, $cycle->progressPercent());
        $this->assertSame(25, $cycle->daysToHarvest());
    }

    public function test_an_overdue_cycle_reports_negative_days_and_full_progress(): void
    {
        $cycle = $this->cycle([
            'planting_date' => now()->subDays(60)->toDateString(),
            'expected_harvest_date' => now()->subDays(5)->toDateString(),
        ]);

        $this->assertSame(-5, $cycle->daysToHarvest());
        $this->assertSame(100, $cycle->progressPercent());

        $this->actingAs($this->manager())
            ->get(route('crops.show', $cycle->crop))
            ->assertSee('5 days overdue');
    }

    public function test_crops_cycles_and_programs_share_one_tab_strip(): void
    {
        $actor = $this->manager();

        foreach (['crops.index', 'crop-cycles.index', 'crop-cycle-templates.index'] as $route) {
            $this->actingAs($actor)->get(route($route))
                ->assertOk()
                ->assertSee('Crop Cycles')
                ->assertSee('sec-tabs', false);
        }
    }
}
