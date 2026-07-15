<?php

namespace Database\Seeders;

use App\Models\ApprovedEmail;
use App\Models\Asset;
use App\Models\Block;
use App\Models\ChartOfAccount;
use App\Models\CostAllocation;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\Customer;
use App\Models\Dispatch;
use App\Models\Farm;
use App\Models\GerminationCheck;
use App\Models\HarvestBatch;
use App\Models\HarvestByProduct;
use App\Models\PlantPopulationCount;
use App\Models\YieldForecast;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\NurseryBatch;
use App\Models\PackhouseLot;
use App\Models\Planting;
use App\Models\QualityCheck;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\SeasonalBudget;
use App\Models\User;
use App\Services\KpiSnapshotService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with realistic Kenyan horticulture data.
     */
    public function run(): void
    {
        // ---- Users (one per role) ----
        $users = [
            ['name' => 'Brian Kam',        'email' => 'admin@trooms.co.ke',       'role' => 'owner',                  'password' => Hash::make('password')],
            ['name' => 'Grace Wanjiru',    'email' => 'grace@trooms.co.ke',       'role' => 'horticulture_manager',  'password' => Hash::make('password')],
            ['name' => 'James Mwangi',     'email' => 'james@trooms.co.ke',       'role' => 'md',                     'password' => Hash::make('password')],
            ['name' => 'Peter Ochieng',    'email' => 'peter@trooms.co.ke',       'role' => 'agronomist',            'password' => Hash::make('password')],
            ['name' => 'Alice Njeri',      'email' => 'alice@trooms.co.ke',       'role' => 'farm_supervisor',       'password' => Hash::make('password')],
            ['name' => 'David Kipchoge',   'email' => 'david@trooms.co.ke',       'role' => 'finance_officer',       'password' => Hash::make('password')],
            ['name' => 'Lucy Wambui',      'email' => 'lucy@trooms.co.ke',        'role' => 'sales_officer',         'password' => Hash::make('password')],
            ['name' => 'Samuel Otieno',    'email' => 'samuel@trooms.co.ke',      'role' => 'storekeeper',           'password' => Hash::make('password')],
            ['name' => 'Mary Akinyi',      'email' => 'mary@trooms.co.ke',        'role' => 'quality_officer',       'password' => Hash::make('password')],
            ['name' => 'John Kamau',       'email' => 'john@trooms.co.ke',        'role' => 'packhouse_supervisor',  'password' => Hash::make('password')],
            ['name' => 'Daniel Mutua',     'email' => 'daniel@trooms.co.ke',      'role' => 'driver',                'password' => Hash::make('password')],
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        // ---- Farms ----
        $naivasha = Farm::create([
            'name' => 'Trooms Naivasha',
            'location' => 'Naivasha, Nakuru County',
            'size_acres' => 120.00,
            'latitude' => -0.7167,
            'longitude' => 36.4333,
        ]);

        $nanyuki = Farm::create([
            'name' => 'Trooms Nanyuki',
            'location' => 'Nanyuki, Laikipia County',
            'size_acres' => 85.50,
            'latitude' => 0.0167,
            'longitude' => 37.0667,
        ]);

        // ---- Blocks ----
        $blockA = Block::create(['farm_id' => $naivasha->id, 'name' => 'Block A — Greenhouse 1', 'size_acres' => 15.0, 'soil_type' => 'Clay loam']);
        $blockB = Block::create(['farm_id' => $naivasha->id, 'name' => 'Block B — Greenhouse 2', 'size_acres' => 12.5, 'soil_type' => 'Clay loam']);
        $blockC = Block::create(['farm_id' => $naivasha->id, 'name' => 'Block C — Open Field',   'size_acres' => 30.0, 'soil_type' => 'Sandy loam']);
        $blockD = Block::create(['farm_id' => $nanyuki->id,  'name' => 'Block D — North',        'size_acres' => 25.0, 'soil_type' => 'Volcanic']);
        $blockE = Block::create(['farm_id' => $nanyuki->id,  'name' => 'Block E — South',        'size_acres' => 20.0, 'soil_type' => 'Red earth']);

        // ---- Crops ----
        // The five crops Trooms actually grows. Swahili names are stored under their
        // correct English names (Sukuma Wiki → Collard Greens, Managu → African Nightshade).
        // Projection template fields (seeds/bed, yield/bed, price/kg, germination)
        // feed the yield & revenue projection engine.
        $bean     = Crop::create(['name' => 'French Bean',        'variety' => 'Samantha',          'crop_type' => 'Vegetable', 'days_to_maturity' => 55, 'expected_yield_per_acre' => 4000,
            'seeds_per_bed' => 1000, 'expected_yield_per_bed_kg' => 10, 'reference_price_per_kg' => 120, 'expected_germination_rate' => 0.85]);
        $capsicum = Crop::create(['name' => 'Capsicum',           'variety' => 'California Wonder',  'crop_type' => 'Vegetable', 'days_to_maturity' => 75, 'expected_yield_per_acre' => 12000,
            'seeds_per_bed' => 200,  'expected_yield_per_bed_kg' => 30, 'reference_price_per_kg' => 90,  'expected_germination_rate' => 0.90,
            'default_labour_budget' => 130000, 'default_input_budget' => 80000, 'default_irrigation_budget' => 35000, 'default_overhead_budget' => 20000]);
        $sukuma   = Crop::create(['name' => 'Collard Greens',     'variety' => 'Thousand Headed',   'crop_type' => 'Vegetable', 'days_to_maturity' => 60, 'expected_yield_per_acre' => 20000,
            'seeds_per_bed' => 500,  'expected_yield_per_bed_kg' => 25, 'reference_price_per_kg' => 40,  'expected_germination_rate' => 0.90,
            'default_labour_budget' => 90000, 'default_input_budget' => 45000, 'default_irrigation_budget' => 20000, 'default_overhead_budget' => 12000]);
        $spinach  = Crop::create(['name' => 'Spinach',            'variety' => 'Fordhook Giant',    'crop_type' => 'Vegetable', 'days_to_maturity' => 45, 'expected_yield_per_acre' => 10000,
            'seeds_per_bed' => 600,  'expected_yield_per_bed_kg' => 15, 'reference_price_per_kg' => 50,  'expected_germination_rate' => 0.88,
            'default_labour_budget' => 80000, 'default_input_budget' => 40000, 'default_irrigation_budget' => 18000, 'default_overhead_budget' => 10000]);
        $managu   = Crop::create(['name' => 'African Nightshade', 'variety' => 'Giant Nightshade',  'crop_type' => 'Vegetable', 'days_to_maturity' => 45, 'expected_yield_per_acre' => 8000,
            'seeds_per_bed' => 800,  'expected_yield_per_bed_kg' => 10, 'reference_price_per_kg' => 60,  'expected_germination_rate' => 0.80]);

        // ---- Assets ----
        Asset::create(['farm_id' => $naivasha->id, 'name' => 'Irrigation Pump #1',  'type' => 'pump',      'purchase_date' => '2024-03-15', 'status' => 'operational', 'current_hours' => 1200]);
        Asset::create(['farm_id' => $naivasha->id, 'name' => 'Irrigation Pump #2',  'type' => 'pump',      'purchase_date' => '2025-01-10', 'status' => 'operational', 'current_hours' => 450]);
        Asset::create(['farm_id' => $naivasha->id, 'name' => 'Delivery Truck KBZ-123','type' => 'vehicle', 'purchase_date' => '2023-07-20', 'status' => 'operational', 'current_mileage' => 45000]);
        Asset::create(['farm_id' => $nanyuki->id,  'name' => 'Tractor JD-5050',     'type' => 'equipment', 'purchase_date' => '2024-06-01', 'status' => 'operational', 'current_hours' => 800]);
        Asset::create(['farm_id' => $nanyuki->id,  'name' => 'Spray Pump #1',       'type' => 'pump',      'purchase_date' => '2025-02-14', 'status' => 'maintenance', 'current_hours' => 200]);
        Asset::create(['farm_id' => $naivasha->id, 'name' => 'Cold Room Unit CR-01','type' => 'equipment', 'purchase_date' => '2024-09-05', 'status' => 'operational', 'current_hours' => 5400]);
        Asset::create(['farm_id' => $naivasha->id, 'name' => 'Standby Generator 40kVA','type' => 'equipment','purchase_date' => '2023-11-30', 'status' => 'operational', 'current_hours' => 1650]);
        Asset::create(['farm_id' => $nanyuki->id,  'name' => 'Refrigerated Truck KDA-456','type' => 'vehicle','purchase_date' => '2024-04-18', 'status' => 'operational', 'current_mileage' => 28000]);
        Asset::create(['farm_id' => $nanyuki->id,  'name' => 'Boom Sprayer BS-02',  'type' => 'equipment', 'purchase_date' => '2025-03-22', 'status' => 'operational', 'current_hours' => 120]);

        // ---- Crop Cycles ----
        $cycle1 = CropCycle::create([
            'block_id' => $blockA->id,
            'crop_id' => $capsicum->id,
            'season_name' => 'Long Rains 2026',
            'planting_date' => '2026-03-01',
            'expected_harvest_date' => '2026-05-01',
            'status' => 'active',
        ]);

        $cycle2 = CropCycle::create([
            'block_id' => $blockC->id,
            'crop_id' => $sukuma->id,
            'season_name' => 'Q3 2026',
            'planting_date' => '2026-07-01',
            'expected_harvest_date' => '2026-10-01',
            'status' => 'planned',
        ]);

        $cycle3 = CropCycle::create([
            'block_id' => $blockD->id,
            'crop_id' => $spinach->id,
            'season_name' => 'Main Crop 2026',
            'planting_date' => '2026-05-15',
            'expected_harvest_date' => '2026-08-30',
            'status' => 'active',
        ]);

        // A finished cycle: French beans grown and harvested earlier in the year.
        $cycle4 = CropCycle::create([
            'block_id' => $blockB->id,
            'crop_id' => $bean->id,
            'season_name' => 'Short Rains 2025/26',
            'planting_date' => '2025-11-15',
            'expected_harvest_date' => '2026-01-10',
            'status' => 'completed',
        ]);

        // A second planned cycle awaiting activation.
        $cycle5 = CropCycle::create([
            'block_id' => $blockE->id,
            'crop_id' => $managu->id,
            'season_name' => 'Q4 2026',
            'planting_date' => '2026-09-01',
            'expected_harvest_date' => '2026-12-30',
            'status' => 'planned',
        ]);

        // A cancelled cycle (e.g. abandoned after poor germination).
        $cycle6 = CropCycle::create([
            'block_id' => $blockC->id,
            'crop_id' => $bean->id,
            'season_name' => 'Q1 2026 (aborted)',
            'planting_date' => '2026-02-01',
            'expected_harvest_date' => '2026-03-28',
            'status' => 'cancelled',
        ]);

        // ---- Nursery Batches & Plantings ----
        // Bed-level planting detail is what the yield projection is built on.
        $capsicumNursery = NurseryBatch::create(['crop_id' => $capsicum->id, 'sow_date' => '2026-02-01', 'expected_ready_date' => '2026-03-01', 'quantity' => 20000, 'status' => 'transplanted']);
        Planting::create(['nursery_batch_id' => $capsicumNursery->id, 'crop_cycle_id' => $cycle1->id, 'quantity' => 16000, 'bed_count' => 80, 'seeds_sown' => 16000, 'area_acres' => 1.5, 'planting_date' => '2026-03-01']);

        $spinachNursery = NurseryBatch::create(['crop_id' => $spinach->id, 'sow_date' => '2026-04-20', 'expected_ready_date' => '2026-05-15', 'quantity' => 40000, 'status' => 'transplanted']);
        Planting::create(['nursery_batch_id' => $spinachNursery->id, 'crop_cycle_id' => $cycle3->id, 'quantity' => 36000, 'bed_count' => 60, 'seeds_sown' => 36000, 'area_acres' => 1.0, 'planting_date' => '2026-05-15']);

        // French beans are drilled bed-by-bed: 90 beds, ~1,000 seeds/bed.
        $beanNursery = NurseryBatch::create(['crop_id' => $bean->id, 'sow_date' => '2025-11-15', 'expected_ready_date' => '2025-11-15', 'quantity' => 95000, 'status' => 'transplanted']);
        Planting::create(['nursery_batch_id' => $beanNursery->id, 'crop_cycle_id' => $cycle4->id, 'quantity' => 90000, 'bed_count' => 90, 'seeds_sown' => 90000, 'area_acres' => 1.0, 'planting_date' => '2025-11-15']);

        // Planned cycles carry a planting plan so their projection shows before planting.
        $sukumaNursery = NurseryBatch::create(['crop_id' => $sukuma->id, 'sow_date' => '2026-06-10', 'expected_ready_date' => '2026-07-01', 'quantity' => 55000, 'status' => 'ready']);
        Planting::create(['nursery_batch_id' => $sukumaNursery->id, 'crop_cycle_id' => $cycle2->id, 'quantity' => 50000, 'bed_count' => 100, 'seeds_sown' => 50000, 'area_acres' => 2.0, 'planting_date' => '2026-07-01']);

        $managuNursery = NurseryBatch::create(['crop_id' => $managu->id, 'sow_date' => '2026-08-10', 'expected_ready_date' => '2026-09-01', 'quantity' => 45000, 'status' => 'ready']);
        Planting::create(['nursery_batch_id' => $managuNursery->id, 'crop_cycle_id' => $cycle5->id, 'quantity' => 40000, 'bed_count' => 50, 'seeds_sown' => 40000, 'area_acres' => 1.0, 'planting_date' => '2026-09-01']);

        // ---- In-season crop monitoring (refines the yield projection) ----
        $agronomist = User::where('role', 'agronomist')->first();

        // French bean cycle (completed): a full monitoring trail — germination check,
        // three declining stand counts, and a pre-harvest sample that forecast ~630 kg
        // against the 650 kg actually harvested.
        GerminationCheck::create(['crop_cycle_id' => $cycle4->id, 'check_date' => '2025-11-20', 'days_after_sowing' => 5, 'sample_size' => 200, 'germinated_count' => 172, 'germination_rate' => 0.860, 'notes' => 'Even emergence, scouted bean fly', 'recorded_by' => $agronomist?->id]);
        PlantPopulationCount::create(['crop_cycle_id' => $cycle4->id, 'count_date' => '2025-12-01', 'days_after_planting' => 16, 'population_rate' => 0.930, 'sample_bed_count' => 10, 'plants_counted' => 930, 'recorded_by' => $agronomist?->id]);
        PlantPopulationCount::create(['crop_cycle_id' => $cycle4->id, 'count_date' => '2025-12-18', 'days_after_planting' => 33, 'population_rate' => 0.880, 'sample_bed_count' => 10, 'plants_counted' => 880, 'notes' => 'Some loss after spray round', 'recorded_by' => $agronomist?->id]);
        PlantPopulationCount::create(['crop_cycle_id' => $cycle4->id, 'count_date' => '2026-01-02', 'days_after_planting' => 48, 'population_rate' => 0.850, 'sample_bed_count' => 10, 'plants_counted' => 850, 'recorded_by' => $agronomist?->id]);
        YieldForecast::create(['crop_cycle_id' => $cycle4->id, 'forecast_date' => '2026-01-05', 'sample_bed_count' => 10, 'total_bed_count' => 90, 'sample_yield_kg' => 70, 'projected_total_kg' => 630, 'notes' => 'Walked 10 of 90 beds @ ~7 kg/bed', 'recorded_by' => $agronomist?->id]);

        // Active cycles: an early germination + stand count so their projection reads "measured".
        GerminationCheck::create(['crop_cycle_id' => $cycle1->id, 'check_date' => '2026-03-06', 'days_after_sowing' => 5, 'sample_size' => 150, 'germinated_count' => 138, 'germination_rate' => 0.920, 'recorded_by' => $agronomist?->id]);
        PlantPopulationCount::create(['crop_cycle_id' => $cycle1->id, 'count_date' => '2026-03-25', 'days_after_planting' => 24, 'population_rate' => 0.950, 'sample_bed_count' => 8, 'recorded_by' => $agronomist?->id]);
        GerminationCheck::create(['crop_cycle_id' => $cycle3->id, 'check_date' => '2026-05-20', 'days_after_sowing' => 5, 'sample_size' => 200, 'germinated_count' => 176, 'germination_rate' => 0.880, 'recorded_by' => $agronomist?->id]);

        // ---- Seasonal Budgets ----
        SeasonalBudget::create([
            'crop_cycle_id' => $cycle1->id,
            'labour_budget' => 150000,
            'input_budget' => 80000,
            'irrigation_budget' => 30000,
            'overhead_budget' => 20000,
            'total_budget' => 280000,
        ]);

        SeasonalBudget::create([
            'crop_cycle_id' => $cycle3->id,
            'labour_budget' => 200000,
            'input_budget' => 120000,
            'irrigation_budget' => 50000,
            'overhead_budget' => 30000,
            'total_budget' => 400000,
        ]);

        // Budget for the completed cycle (used to compare actual vs. budget on close-out).
        SeasonalBudget::create([
            'crop_cycle_id' => $cycle4->id,
            'labour_budget' => 90000,
            'input_budget' => 55000,
            'irrigation_budget' => 20000,
            'overhead_budget' => 15000,
            'total_budget' => 180000,
        ]);

        // ---- Resolve role users referenced below ----
        $supervisor = User::where('role', 'farm_supervisor')->first();
        $storekeeper = User::where('role', 'storekeeper')->first();
        $inspector = User::where('role', 'quality_officer')->first();
        $driver = User::where('role', 'driver')->first();
        $truck = Asset::where('type', 'vehicle')->first();

        // ---- Module 10: Inventory & Stores ----
        $npk = InventoryItem::create(['farm_id' => $naivasha->id, 'name' => 'NPK 17-17-17', 'category' => 'fertilizer', 'stage' => 'pre_harvest_input', 'unit' => 'kg', 'reorder_level' => 200]);
        $fungicide = InventoryItem::create(['farm_id' => $naivasha->id, 'name' => 'Copper Fungicide', 'category' => 'chemical', 'stage' => 'pre_harvest_input', 'unit' => 'litre', 'reorder_level' => 20]);
        $cartons = InventoryItem::create(['farm_id' => $naivasha->id, 'name' => '4kg Export Carton', 'category' => 'packaging', 'stage' => 'post_harvest_packaging', 'unit' => 'unit', 'reorder_level' => 500]);
        $can = InventoryItem::create(['farm_id' => $naivasha->id, 'name' => 'CAN 26% Nitrogen', 'category' => 'fertilizer', 'stage' => 'pre_harvest_input', 'unit' => 'kg', 'reorder_level' => 150]);
        $pesticide = InventoryItem::create(['farm_id' => $nanyuki->id, 'name' => 'Lambda-Cyhalothrin', 'category' => 'chemical', 'stage' => 'pre_harvest_input', 'unit' => 'litre', 'reorder_level' => 15]);
        $crates = InventoryItem::create(['farm_id' => $nanyuki->id, 'name' => 'Plastic Field Crate', 'category' => 'packaging', 'stage' => 'post_harvest_packaging', 'unit' => 'unit', 'reorder_level' => 100]);

        InventoryTransaction::create(['inventory_item_id' => $npk->id, 'farm_id' => $naivasha->id, 'type' => 'receipt', 'quantity' => 500, 'transaction_date' => '2026-06-01', 'reference' => 'GRN-1001', 'cost' => 45000]);
        $npkIssue = InventoryTransaction::create(['inventory_item_id' => $npk->id, 'farm_id' => $naivasha->id, 'crop_cycle_id' => $cycle1->id, 'type' => 'issue', 'quantity' => 120, 'transaction_date' => '2026-06-10', 'reference' => 'ISS-2001', 'cost' => 10800]);
        // Issues linked to a crop cycle automatically feed Cost Allocation.
        CostAllocation::create(['crop_cycle_id' => $cycle1->id, 'source_type' => 'inventory', 'source_id' => $npkIssue->id, 'amount' => 10800, 'allocation_date' => '2026-06-10', 'description' => 'Inventory issue: NPK 17-17-17 (120 kg)']);
        InventoryTransaction::create(['inventory_item_id' => $fungicide->id, 'farm_id' => $naivasha->id, 'type' => 'receipt', 'quantity' => 30, 'transaction_date' => '2026-06-01', 'reference' => 'GRN-1002', 'cost' => 18000]);
        // Cartons deliberately left below reorder level to demonstrate the low_inventory alert.
        InventoryTransaction::create(['inventory_item_id' => $cartons->id, 'farm_id' => $naivasha->id, 'type' => 'receipt', 'quantity' => 300, 'transaction_date' => '2026-06-05', 'reference' => 'GRN-1003', 'cost' => 9000]);

        // Additional stock movements across both farms.
        InventoryTransaction::create(['inventory_item_id' => $can->id, 'farm_id' => $naivasha->id, 'type' => 'receipt', 'quantity' => 400, 'transaction_date' => '2026-06-12', 'reference' => 'GRN-1004', 'cost' => 32000]);
        $canIssue = InventoryTransaction::create(['inventory_item_id' => $can->id, 'farm_id' => $naivasha->id, 'crop_cycle_id' => $cycle3->id, 'type' => 'issue', 'quantity' => 90, 'transaction_date' => '2026-06-20', 'reference' => 'ISS-2002', 'cost' => 7200]);
        CostAllocation::create(['crop_cycle_id' => $cycle3->id, 'source_type' => 'inventory', 'source_id' => $canIssue->id, 'amount' => 7200, 'allocation_date' => '2026-06-20', 'description' => 'Inventory issue: CAN 26% Nitrogen (90 kg)']);
        InventoryTransaction::create(['inventory_item_id' => $pesticide->id, 'farm_id' => $nanyuki->id, 'type' => 'receipt', 'quantity' => 25, 'transaction_date' => '2026-06-15', 'reference' => 'GRN-1005', 'cost' => 21000]);
        InventoryTransaction::create(['inventory_item_id' => $crates->id, 'farm_id' => $nanyuki->id, 'type' => 'receipt', 'quantity' => 250, 'transaction_date' => '2026-06-18', 'reference' => 'GRN-1006', 'cost' => 37500]);

        // ---- Module 11: Harvest Management ----
        $harvest1 = HarvestBatch::create([
            'crop_cycle_id' => $cycle1->id, 'block_id' => $blockA->id, 'harvest_date' => '2026-07-08',
            'quantity_kg' => 1200, 'quality_grade' => 'Grade A', 'rejects_kg' => 80, 'harvested_by' => $supervisor?->id,
        ]);
        $harvest2 = HarvestBatch::create([
            'crop_cycle_id' => $cycle3->id, 'block_id' => $blockD->id, 'harvest_date' => '2026-07-09',
            'quantity_kg' => 900, 'quality_grade' => 'Grade A', 'rejects_kg' => 40, 'harvested_by' => $supervisor?->id,
        ]);
        // Harvest from the completed French bean cycle (closed out in January).
        $harvest3 = HarvestBatch::create([
            'crop_cycle_id' => $cycle4->id, 'block_id' => $blockB->id, 'harvest_date' => '2026-01-08',
            'quantity_kg' => 650, 'quality_grade' => 'Grade A', 'rejects_kg' => 30, 'harvested_by' => $supervisor?->id,
        ]);
        // A second pick from the active capsicum cycle, graded lower.
        $harvest4 = HarvestBatch::create([
            'crop_cycle_id' => $cycle1->id, 'block_id' => $blockA->id, 'harvest_date' => '2026-07-09',
            'quantity_kg' => 1050, 'quality_grade' => 'Grade B', 'rejects_kg' => 130, 'harvested_by' => $supervisor?->id,
        ]);

        // Weight confirmations: a second person (quality officer) verifies the scale.
        $harvest1->update(['confirmed_by' => $inspector?->id, 'confirmed_at' => '2026-07-08 16:30:00']);
        $harvest3->update(['confirmed_by' => $inspector?->id, 'confirmed_at' => '2026-01-08 15:00:00']);

        // By-products recovered alongside the main pick.
        HarvestByProduct::create(['harvest_batch_id' => $harvest3->id, 'name' => 'Offcut bean tips', 'quantity_kg' => 35, 'notes' => 'Sold to local market']);
        HarvestByProduct::create(['harvest_batch_id' => $harvest1->id, 'name' => 'Trimmed leaves', 'quantity_kg' => 20, 'notes' => 'Compost / animal feed']);

        // ---- Module 12: Packhouse & Traceability ----
        $lot1 = PackhouseLot::create([
            'harvest_batch_id' => $harvest1->id, 'lot_number' => 'LOT-0001', 'pack_date' => '2026-07-08',
            'quantity_packed' => 800, 'packaging_type' => '4kg carton', 'traceability_code' => 'TRC-' . strtoupper(Str::random(10)),
        ]);
        $lot2 = PackhouseLot::create([
            'harvest_batch_id' => $harvest2->id, 'lot_number' => 'LOT-0002', 'pack_date' => '2026-07-09',
            'quantity_packed' => 600, 'packaging_type' => '4kg carton', 'traceability_code' => 'TRC-' . strtoupper(Str::random(10)),
        ]);
        $lot3 = PackhouseLot::create([
            'harvest_batch_id' => $harvest3->id, 'lot_number' => 'LOT-0003', 'pack_date' => '2026-01-08',
            'quantity_packed' => 500, 'packaging_type' => '5kg vented carton', 'traceability_code' => 'TRC-' . strtoupper(Str::random(10)),
        ]);
        $lot4 = PackhouseLot::create([
            'harvest_batch_id' => $harvest4->id, 'lot_number' => 'LOT-0004', 'pack_date' => '2026-07-09',
            'quantity_packed' => 700, 'packaging_type' => '4kg carton', 'traceability_code' => 'TRC-' . strtoupper(Str::random(10)),
        ]);

        // ---- Module 13: Quality Assurance ----
        QualityCheck::create([
            'packhouse_lot_id' => $lot1->id, 'check_date' => '2026-07-08', 'result' => 'pass',
            'parameters' => ['Brix' => '12', 'Firmness' => '4.5', 'Defects' => 'none'], 'inspector_id' => $inspector?->id,
        ]);
        QualityCheck::create([
            'packhouse_lot_id' => $lot2->id, 'check_date' => '2026-07-09', 'result' => 'fail',
            'parameters' => ['Brix' => '9', 'Defects' => 'bruising'], 'inspector_id' => $inspector?->id,
        ]);
        QualityCheck::create([
            'packhouse_lot_id' => $lot3->id, 'check_date' => '2026-01-08', 'result' => 'pass',
            'parameters' => ['Length' => '11cm', 'Firmness' => '4.8', 'Defects' => 'none'], 'inspector_id' => $inspector?->id,
        ]);
        QualityCheck::create([
            'packhouse_lot_id' => $lot4->id, 'check_date' => '2026-07-09', 'result' => 'pass',
            'parameters' => ['Brix' => '11', 'Firmness' => '4.2', 'Defects' => 'minor spotting'], 'inspector_id' => $inspector?->id,
        ]);

        // ---- Module 14: Sales & Customer Contracts ----
        $customer = Customer::create([
            'name' => 'FreshMart Europe Ltd', 'contact' => 'orders@freshmart.eu',
            'contract_terms' => 'Weekly export, EUR pricing, FOB Nairobi.', 'price_list' => 'Export 2026',
        ]);
        $localCustomer = Customer::create([
            'name' => 'Nairobi Grocers Co-op', 'contact' => '+254 700 000000',
            'contract_terms' => 'Local wholesale, net 14 days.', 'price_list' => 'Local 2026',
        ]);
        $ukCustomer = Customer::create([
            'name' => 'GreenLeaf UK Ltd', 'contact' => 'buying@greenleaf.co.uk',
            'contract_terms' => 'Airfreight vegetables, GBP pricing, net 30.', 'price_list' => 'Export 2026',
        ]);

        $order1 = SalesOrder::create([
            'customer_id' => $customer->id, 'crop_id' => $capsicum->id, 'order_date' => '2026-07-08',
            'requested_quantity' => 800, 'status' => 'allocated', 'delivery_date' => '2026-07-12',
        ]);
        SalesOrderLine::create([
            'sales_order_id' => $order1->id, 'packhouse_lot_id' => $lot1->id, 'quantity' => 800, 'unit_price' => 350,
        ]);

        // A pending order near its delivery date with no allocation → order_at_risk.
        SalesOrder::create([
            'customer_id' => $customer->id, 'crop_id' => $sukuma->id, 'order_date' => '2026-07-09',
            'requested_quantity' => 500, 'status' => 'pending', 'delivery_date' => now()->addDays(3)->toDateString(),
        ]);

        // A completed local wholesale order fulfilled from the second capsicum pick.
        $order2 = SalesOrder::create([
            'customer_id' => $localCustomer->id, 'crop_id' => $capsicum->id, 'order_date' => '2026-07-09',
            'requested_quantity' => 700, 'status' => 'fulfilled', 'delivery_date' => '2026-07-10',
        ]);
        SalesOrderLine::create([
            'sales_order_id' => $order2->id, 'packhouse_lot_id' => $lot4->id, 'quantity' => 700, 'unit_price' => 180,
        ]);

        // An allocated export order for the completed French bean lot.
        $order3 = SalesOrder::create([
            'customer_id' => $ukCustomer->id, 'crop_id' => $bean->id, 'order_date' => '2026-01-09',
            'requested_quantity' => 500, 'status' => 'allocated', 'delivery_date' => '2026-01-12',
        ]);
        SalesOrderLine::create([
            'sales_order_id' => $order3->id, 'packhouse_lot_id' => $lot3->id, 'quantity' => 500, 'unit_price' => 420,
        ]);

        // ---- Module 15: Logistics & Dispatch ----
        Dispatch::create([
            'sales_order_id' => $order1->id, 'vehicle_asset_id' => $truck?->id, 'driver_id' => $driver?->id,
            'dispatch_date' => '2026-07-11', 'route' => 'Naivasha → Nairobi JKIA', 'status' => 'scheduled',
        ]);
        Dispatch::create([
            'sales_order_id' => $order2->id, 'vehicle_asset_id' => $truck?->id, 'driver_id' => $driver?->id,
            'dispatch_date' => '2026-07-10', 'route' => 'Naivasha → Nairobi CBD', 'status' => 'delivered',
        ]);
        Dispatch::create([
            'sales_order_id' => $order3->id, 'vehicle_asset_id' => $truck?->id, 'driver_id' => $driver?->id,
            'dispatch_date' => '2026-01-11', 'route' => 'Nanyuki → Nairobi JKIA', 'status' => 'in_transit',
        ]);

        // ---- Module 16: Finance (Chart of Accounts) ----
        ChartOfAccount::insert([
            ['code' => '1000', 'name' => 'Cash & Bank',           'type' => 'asset',   'created_at' => now(), 'updated_at' => now()],
            ['code' => '4000', 'name' => 'Produce Sales',         'type' => 'income',  'created_at' => now(), 'updated_at' => now()],
            ['code' => '5000', 'name' => 'Farm Operating Costs',  'type' => 'expense', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ---- Approved-email allowlist (onboarding) ----
        // Existing seeded accounts, recorded as already-registered approvals.
        $owner = User::where('role', 'owner')->first();
        foreach (User::all() as $existing) {
            ApprovedEmail::create([
                'email' => $existing->email,
                'role' => $existing->role,
                'invited_by' => $owner?->id,
                'registered_at' => now(),
            ]);
        }
        // A couple of pending invitations awaiting self-registration.
        ApprovedEmail::create(['email' => 'newagronomist@trooms.co.ke', 'role' => 'agronomist',      'invited_by' => $owner?->id]);
        ApprovedEmail::create(['email' => 'newdriver@trooms.co.ke',     'role' => 'driver',          'invited_by' => $owner?->id]);
        ApprovedEmail::create(['email' => 'newstores@trooms.co.ke',     'role' => 'storekeeper',     'invited_by' => $owner?->id]);

        // ---- Module 17: Executive KPI snapshots ----
        $this->call(WorkerSeeder::class);
        $this->call(StableSeeder::class);
        $this->call(ToolAssetSeeder::class);

        (new KpiSnapshotService())->recompute();

        // Backfill ~8 days of KPI history (jittered around today's values) so the
        // executive dashboard sparklines show a trend rather than a single point.
        $latest = \App\Models\KpiSnapshot::max('snapshot_date');
        if ($latest) {
            $current = \App\Models\KpiSnapshot::where('snapshot_date', $latest)->get();
            foreach (range(8, 1) as $daysAgo) {
                $date = \Illuminate\Support\Carbon::parse($latest)->subDays($daysAgo)->startOfDay();
                foreach ($current as $snap) {
                    $factor = 0.82 + (mt_rand(0, 36) / 100); // 0.82–1.18
                    \App\Models\KpiSnapshot::updateOrCreate(
                        ['snapshot_date' => $date, 'key' => $snap->key],
                        ['value' => round((float) $snap->value * $factor, 2), 'unit' => $snap->unit, 'meta' => $snap->meta]
                    );
                }
            }
        }
    }
}
