<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\CropCycleTemplate;
use App\Models\Farm;
use App\Models\SeasonalBudget;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * A worked example of the spec redesign: one template with stages and a spray
 * schedule, and an active cycle planted far enough back that some points have
 * already come due. Run `horticulture:send-reminders` after seeding to see the
 * tasks appear.
 */
class SpecDemoSeeder extends Seeder
{
    public function run(): void
    {
        $farm = Farm::firstOrCreate(
            ['name' => 'Trooms House Farm'],
            ['location' => 'Naivasha', 'size_acres' => 40]
        );

        $block = Block::firstOrCreate(
            ['farm_id' => $farm->id, 'name' => 'Block A'],
            ['size_acres' => 2, 'soil_type' => 'Loam']
        );

        $crop = Crop::firstOrCreate(
            ['name' => 'Tomato'],
            ['crop_type' => 'Vegetable', 'variety' => 'Roma', 'days_to_maturity' => 90, 'expected_yield_per_acre' => 25000]
        );

        // The reusable plan: three stages, four schedule points.
        $template = CropCycleTemplate::firstOrCreate(
            ['crop_name' => 'Tomato', 'variety' => 'Roma'],
            ['crop_id' => $crop->id, 'total_cycle_days' => 90, 'description' => 'Standard 90-day Roma tomato programme.']
        );

        if ($template->stages()->count() === 0) {
            $veg = $template->stages()->create(['stage_name' => 'Vegetative', 'start_day_offset' => 0, 'end_day_offset' => 40, 'sort_order' => 1]);
            $flower = $template->stages()->create(['stage_name' => 'Flowering', 'start_day_offset' => 41, 'end_day_offset' => 54, 'sort_order' => 2]);
            $fruit = $template->stages()->create(['stage_name' => 'Fruiting', 'start_day_offset' => 55, 'end_day_offset' => 90, 'sort_order' => 3]);

            $template->schedulePoints()->createMany([
                ['day_offset' => 14, 'activity_type' => 'input', 'product_name' => 'CAN top-dress', 'purpose' => 'nitrogen boost', 'dosage' => '5g/plant', 'crop_cycle_stage_id' => $veg->id],
                ['day_offset' => 30, 'activity_type' => 'spray', 'product_name' => 'Actara', 'purpose' => 'aphid control', 'dosage' => '5g/20L', 'pre_harvest_interval_days' => 7, 'crop_cycle_stage_id' => $veg->id],
                ['day_offset' => 45, 'activity_type' => 'foliar_feed', 'product_name' => 'Calcium foliar', 'purpose' => 'blossom-end rot prevention', 'dosage' => '20ml/20L', 'crop_cycle_stage_id' => $flower->id],
                ['day_offset' => 60, 'activity_type' => 'spray', 'product_name' => 'Mancozeb fungicide', 'purpose' => 'blight prevention', 'dosage' => '2kg/ha', 'pre_harvest_interval_days' => 10, 'crop_cycle_stage_id' => $fruit->id],
                ['day_offset' => 85, 'activity_type' => 'harvest_check', 'purpose' => 'maturity check', 'crop_cycle_stage_id' => $fruit->id],
            ]);
        }

        // An active cycle planted 62 days ago: days 14, 30 and 45 are overdue,
        // day 60 has just come due, day 85 is still ahead.
        $cycle = CropCycle::firstOrCreate(
            ['block_id' => $block->id, 'season_name' => 'Long Rains 2026'],
            [
                'crop_id' => $crop->id,
                'crop_cycle_template_id' => $template->id,
                'planting_date' => now()->subDays(62)->toDateString(),
                'expected_harvest_date' => now()->subDays(62)->addDays(90)->toDateString(),
                'status' => 'active',
            ]
        );

        SeasonalBudget::firstOrCreate(
            ['crop_cycle_id' => $cycle->id],
            ['labour_budget' => 50000, 'input_budget' => 40000, 'irrigation_budget' => 15000, 'overhead_budget' => 10000, 'total_budget' => 115000]
        );

        // Someone for the reminders to fall to.
        User::firstOrCreate(
            ['email' => 'supervisor@trooms.house'],
            ['name' => 'Field Supervisor', 'role' => 'farm_supervisor', 'is_active' => true, 'password' => bcrypt('password')]
        );

        $this->command->info('Spec demo seeded: template "Tomato (Roma)" with 5 schedule points, active cycle "Long Rains 2026" on Block A.');
        $this->command->info('Now run:  php artisan horticulture:send-reminders');
    }
}
