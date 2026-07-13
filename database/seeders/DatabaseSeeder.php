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
use App\Models\HarvestBatch;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\PackhouseLot;
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
        $rose    = Crop::create(['name' => 'Rose',       'variety' => 'Red Naomi',   'crop_type' => 'Flower',    'days_to_maturity' => 60,  'expected_yield_per_acre' => 8000,
            'default_labour_budget' => 150000, 'default_input_budget' => 80000, 'default_irrigation_budget' => 30000, 'default_overhead_budget' => 20000]);
        $avocado = Crop::create(['name' => 'Avocado',    'variety' => 'Hass',        'crop_type' => 'Fruit',     'days_to_maturity' => 365, 'expected_yield_per_acre' => 6000,
            'default_labour_budget' => 200000, 'default_input_budget' => 120000, 'default_irrigation_budget' => 50000, 'default_overhead_budget' => 30000]);
        $tomato  = Crop::create(['name' => 'Tomato',     'variety' => 'Anna F1',     'crop_type' => 'Vegetable', 'days_to_maturity' => 90,  'expected_yield_per_acre' => 25000,
            'default_labour_budget' => 110000, 'default_input_budget' => 70000, 'default_irrigation_budget' => 25000, 'default_overhead_budget' => 15000]);
        $bean    = Crop::create(['name' => 'French Bean','variety' => 'Samantha',    'crop_type' => 'Vegetable', 'days_to_maturity' => 55,  'expected_yield_per_acre' => 4000]);
        $straw   = Crop::create(['name' => 'Strawberry', 'variety' => 'Chandler',   'crop_type' => 'Fruit',     'days_to_maturity' => 120, 'expected_yield_per_acre' => 15000]);

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
            'crop_id' => $rose->id,
            'season_name' => 'Long Rains 2026',
            'planting_date' => '2026-03-01',
            'expected_harvest_date' => '2026-05-01',
            'status' => 'active',
        ]);

        $cycle2 = CropCycle::create([
            'block_id' => $blockC->id,
            'crop_id' => $tomato->id,
            'season_name' => 'Q3 2026',
            'planting_date' => '2026-07-01',
            'expected_harvest_date' => '2026-10-01',
            'status' => 'planned',
        ]);

        $cycle3 = CropCycle::create([
            'block_id' => $blockD->id,
            'crop_id' => $avocado->id,
            'season_name' => 'Annual 2026',
            'planting_date' => '2026-01-15',
            'expected_harvest_date' => '2027-01-15',
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
            'crop_id' => $straw->id,
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
        $npk = InventoryItem::create(['farm_id' => $naivasha->id, 'name' => 'NPK 17-17-17', 'category' => 'fertilizer', 'unit' => 'kg', 'reorder_level' => 200]);
        $fungicide = InventoryItem::create(['farm_id' => $naivasha->id, 'name' => 'Copper Fungicide', 'category' => 'chemical', 'unit' => 'litre', 'reorder_level' => 20]);
        $cartons = InventoryItem::create(['farm_id' => $naivasha->id, 'name' => '4kg Export Carton', 'category' => 'packaging', 'unit' => 'unit', 'reorder_level' => 500]);
        $can = InventoryItem::create(['farm_id' => $naivasha->id, 'name' => 'CAN 26% Nitrogen', 'category' => 'fertilizer', 'unit' => 'kg', 'reorder_level' => 150]);
        $pesticide = InventoryItem::create(['farm_id' => $nanyuki->id, 'name' => 'Lambda-Cyhalothrin', 'category' => 'chemical', 'unit' => 'litre', 'reorder_level' => 15]);
        $crates = InventoryItem::create(['farm_id' => $nanyuki->id, 'name' => 'Plastic Field Crate', 'category' => 'packaging', 'unit' => 'unit', 'reorder_level' => 100]);

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
        // A second pick from the active rose cycle, graded lower.
        $harvest4 = HarvestBatch::create([
            'crop_cycle_id' => $cycle1->id, 'block_id' => $blockA->id, 'harvest_date' => '2026-07-09',
            'quantity_kg' => 1050, 'quality_grade' => 'Grade B', 'rejects_kg' => 130, 'harvested_by' => $supervisor?->id,
        ]);

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
            'quantity_packed' => 500, 'packaging_type' => '2kg punnet', 'traceability_code' => 'TRC-' . strtoupper(Str::random(10)),
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
            'name' => 'BerryGood UK Ltd', 'contact' => 'buying@berrygood.co.uk',
            'contract_terms' => 'Airfreight punnets, GBP pricing, net 30.', 'price_list' => 'Export 2026',
        ]);

        $order1 = SalesOrder::create([
            'customer_id' => $customer->id, 'crop_id' => $rose->id, 'order_date' => '2026-07-08',
            'requested_quantity' => 800, 'status' => 'allocated', 'delivery_date' => '2026-07-12',
        ]);
        SalesOrderLine::create([
            'sales_order_id' => $order1->id, 'packhouse_lot_id' => $lot1->id, 'quantity' => 800, 'unit_price' => 350,
        ]);

        // A pending order near its delivery date with no allocation → order_at_risk.
        SalesOrder::create([
            'customer_id' => $customer->id, 'crop_id' => $avocado->id, 'order_date' => '2026-07-09',
            'requested_quantity' => 500, 'status' => 'pending', 'delivery_date' => now()->addDays(3)->toDateString(),
        ]);

        // A completed local wholesale order fulfilled from the second rose pick.
        $order2 = SalesOrder::create([
            'customer_id' => $localCustomer->id, 'crop_id' => $rose->id, 'order_date' => '2026-07-09',
            'requested_quantity' => 700, 'status' => 'fulfilled', 'delivery_date' => '2026-07-10',
        ]);
        SalesOrderLine::create([
            'sales_order_id' => $order2->id, 'packhouse_lot_id' => $lot4->id, 'quantity' => 700, 'unit_price' => 180,
        ]);

        // An allocated export order for the completed strawberry lot.
        $order3 = SalesOrder::create([
            'customer_id' => $ukCustomer->id, 'crop_id' => $straw->id, 'order_date' => '2026-01-09',
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
        (new KpiSnapshotService())->recompute();

        // Backfill ~8 days of KPI history (jittered around today's values) so the
        // executive dashboard sparklines show a trend rather than a single point.
        $latest = \App\Models\KpiSnapshot::max('snapshot_date');
        if ($latest) {
            $current = \App\Models\KpiSnapshot::where('snapshot_date', $latest)->get();
            foreach (range(8, 1) as $daysAgo) {
                $date = \Illuminate\Support\Carbon::parse($latest)->subDays($daysAgo)->toDateString();
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
