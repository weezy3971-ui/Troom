<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\Block;
use App\Models\CropCycle;
use App\Models\Dispatch;
use App\Models\HarvestBatch;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\PackhouseLot;
use App\Models\QualityCheck;
use App\Models\SalesOrder;
use App\Models\SeasonalBudget;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all()->keyBy('role');
        $owner     = $users['owner']         ?? User::first();
        $manager   = $users['horticulture_manager'] ?? $owner;
        $supervisor = $users['farm_supervisor'] ?? $owner;
        $agronomist = $users['agronomist']     ?? $owner;
        $storekeeper = $users['storekeeper']   ?? $owner;
        $quality   = $users['quality_officer'] ?? $owner;
        $sales     = $users['sales_officer']   ?? $owner;
        $finance   = $users['finance_officer'] ?? $owner;
        $driver    = $users['driver']          ?? $owner;

        $entries = [
            // ── Farms & Blocks ──────────────────────────────────────────
            ['days' => 90, 'user' => $owner,      'action' => 'created', 'subject' => \App\Models\Farm::where('name','like','%Naivasha%')->first(), 'desc' => 'Created Farm: Trooms Naivasha (120 acres, Naivasha, Nakuru County)'],
            ['days' => 89, 'user' => $owner,      'action' => 'created', 'subject' => \App\Models\Farm::where('name','like','%Nanyuki%')->first(), 'desc' => 'Created Farm: Trooms Nanyuki (85.5 acres, Nanyuki, Laikipia County)'],
            ['days' => 88, 'user' => $manager,    'action' => 'created', 'subject' => Block::where('name','like','%Block A%')->first(), 'desc' => 'Created Block: Block A — Greenhouse 1 (15 acres, clay loam)'],
            ['days' => 88, 'user' => $manager,    'action' => 'created', 'subject' => Block::where('name','like','%Block B%')->first(), 'desc' => 'Created Block: Block B — Greenhouse 2 (12.5 acres, clay loam)'],
            ['days' => 87, 'user' => $manager,    'action' => 'created', 'subject' => Block::where('name','like','%Block C%')->first(), 'desc' => 'Created Block: Block C — Open Field (30 acres, sandy loam)'],

            // ── Crop Cycles ─────────────────────────────────────────────
            ['days' => 60, 'user' => $agronomist, 'action' => 'created', 'subject' => CropCycle::where('season_name','Long Rains 2026')->first(), 'desc' => 'Created Crop Cycle: Long Rains 2026 — Rose (Red Naomi) on Block A, planted 1 Mar 2026'],
            ['days' => 55, 'user' => $agronomist, 'action' => 'created', 'subject' => CropCycle::where('season_name','Annual 2026')->first(), 'desc' => 'Created Crop Cycle: Annual 2026 — Avocado (Hass) on Block D, planted 15 Jan 2026'],
            ['days' => 30, 'user' => $manager,    'action' => 'created', 'subject' => CropCycle::where('season_name','Q3 2026')->first(), 'desc' => 'Created Crop Cycle: Q3 2026 — Tomato (Anna F1) on Block C, planned for Jul–Oct 2026'],
            ['days' => 10, 'user' => $manager,    'action' => 'updated', 'subject' => CropCycle::where('season_name','Long Rains 2026')->first(), 'desc' => 'Updated Crop Cycle: Long Rains 2026 — status changed from planned to active'],
            ['days' =>  5, 'user' => $agronomist, 'action' => 'created', 'subject' => CropCycle::where('season_name','Q4 2026')->first(), 'desc' => 'Created Crop Cycle: Q4 2026 — Strawberry (Chandler) on Block E, planting Sep 2026'],

            // ── Seasonal Budgets ─────────────────────────────────────────
            ['days' => 58, 'user' => $finance,    'action' => 'created', 'subject' => SeasonalBudget::first(), 'desc' => 'Created Seasonal Budget: Long Rains 2026 — KES 280,000 (labour 150k, inputs 80k, irrigation 30k, overhead 20k)'],
            ['days' => 53, 'user' => $finance,    'action' => 'created', 'subject' => SeasonalBudget::skip(1)->first(), 'desc' => 'Created Seasonal Budget: Annual 2026 Avocado — KES 400,000 approved'],
            ['days' =>  3, 'user' => $finance,    'action' => 'updated', 'subject' => SeasonalBudget::first(), 'desc' => 'Updated Seasonal Budget: Long Rains 2026 — irrigation budget revised from KES 30,000 to 35,000 after pump repair'],

            // ── Assets ───────────────────────────────────────────────────
            ['days' => 45, 'user' => $manager,    'action' => 'created', 'subject' => Asset::where('name','Irrigation Pump #2')->first(), 'desc' => 'Created Asset: Irrigation Pump #2 — operational, purchased Jan 2025'],
            ['days' => 20, 'user' => $supervisor, 'action' => 'updated', 'subject' => Asset::where('name','Spray Pump #1')->first(), 'desc' => 'Updated Asset: Spray Pump #1 — status changed to maintenance (scheduled service at 200 hrs)'],
            ['days' =>  7, 'user' => $supervisor, 'action' => 'updated', 'subject' => Asset::where('name','Spray Pump #1')->first(), 'desc' => 'Updated Asset: Spray Pump #1 — status restored to operational after service'],
            ['days' =>  2, 'user' => $supervisor, 'action' => 'updated', 'subject' => Asset::where('type','pump')->where('status','down')->first() ?? Asset::first(), 'desc' => 'Updated Asset: Irrigation Pump #1 — status changed to down (motor fault reported by field team)'],

            // ── Inventory ────────────────────────────────────────────────
            ['days' => 30, 'user' => $storekeeper,'action' => 'created', 'subject' => InventoryTransaction::where('reference','GRN-1001')->first(), 'desc' => 'Received stock: NPK 17-17-17 — 500 kg received, GRN-1001, KES 45,000'],
            ['days' => 22, 'user' => $storekeeper,'action' => 'created', 'subject' => InventoryTransaction::where('reference','GRN-1003')->first(), 'desc' => 'Received stock: 4kg Export Carton — 300 units received, GRN-1003 (below reorder level of 500)'],
            ['days' => 20, 'user' => $storekeeper,'action' => 'created', 'subject' => InventoryTransaction::where('reference','ISS-2001')->first(), 'desc' => 'Issued from store: NPK 17-17-17 — 120 kg issued to Long Rains 2026 crop cycle, ISS-2001'],
            ['days' => 15, 'user' => $storekeeper,'action' => 'created', 'subject' => InventoryTransaction::where('reference','GRN-1004')->first(), 'desc' => 'Received stock: CAN 26% Nitrogen — 400 kg, GRN-1004, KES 32,000'],
            ['days' => 12, 'user' => $storekeeper,'action' => 'created', 'subject' => InventoryTransaction::where('reference','ISS-2002')->first(), 'desc' => 'Issued from store: CAN 26% Nitrogen — 90 kg to Annual 2026 Avocado cycle, ISS-2002'],

            // ── Harvest ──────────────────────────────────────────────────
            ['days' =>  2, 'user' => $supervisor, 'action' => 'created', 'subject' => HarvestBatch::where('quantity_kg',1200)->first(), 'desc' => 'Recorded Harvest Batch: 1,200 kg Rose (Grade A), 80 kg rejects — Block A Greenhouse 1, 8 Jul 2026'],
            ['days' =>  1, 'user' => $supervisor, 'action' => 'created', 'subject' => HarvestBatch::where('quantity_kg',900)->first(), 'desc' => 'Recorded Harvest Batch: 900 kg Avocado (Grade A), 40 kg rejects — Block D North, 9 Jul 2026'],
            ['days' =>  1, 'user' => $supervisor, 'action' => 'created', 'subject' => HarvestBatch::where('quantity_kg',1050)->first(), 'desc' => 'Recorded Harvest Batch: 1,050 kg Rose (Grade B), 130 kg rejects — Block A second pick, 9 Jul 2026'],

            // ── Packhouse & Traceability ──────────────────────────────────
            ['days' =>  2, 'user' => $supervisor, 'action' => 'created', 'subject' => PackhouseLot::where('lot_number','LOT-0001')->first(), 'desc' => 'Packed Lot LOT-0001: 800 units × 4kg export carton from harvest batch HB-001'],
            ['days' =>  1, 'user' => $supervisor, 'action' => 'created', 'subject' => PackhouseLot::where('lot_number','LOT-0002')->first(), 'desc' => 'Packed Lot LOT-0002: 600 units × 4kg export carton from harvest batch HB-002'],
            ['days' =>  1, 'user' => $supervisor, 'action' => 'created', 'subject' => PackhouseLot::where('lot_number','LOT-0004')->first(), 'desc' => 'Packed Lot LOT-0004: 700 units × 4kg export carton from Grade B rose pick'],

            // ── Quality Checks ────────────────────────────────────────────
            ['days' =>  2, 'user' => $quality,    'action' => 'created', 'subject' => QualityCheck::where('result','pass')->first(), 'desc' => 'Quality Check PASS — LOT-0001: Brix 12°, Firmness 4.5, no defects. Cleared for export.'],
            ['days' =>  1, 'user' => $quality,    'action' => 'created', 'subject' => QualityCheck::where('result','fail')->first(), 'desc' => 'Quality Check FAIL — LOT-0002: Brix 9° (below 10° threshold), bruising detected. Held for review.'],
            ['days' =>  1, 'user' => $quality,    'action' => 'updated', 'subject' => QualityCheck::where('result','fail')->first(), 'desc' => 'Quality Check updated — LOT-0002: lot regraded to local market, not exported'],

            // ── Sales Orders ──────────────────────────────────────────────
            ['days' =>  2, 'user' => $sales,      'action' => 'created', 'subject' => SalesOrder::where('status','allocated')->first(), 'desc' => 'Created Sales Order #1: FreshMart Europe Ltd — 800 kg Rose, delivery 12 Jul 2026, LOT-0001 allocated'],
            ['days' =>  1, 'user' => $sales,      'action' => 'created', 'subject' => SalesOrder::where('status','fulfilled')->first(), 'desc' => 'Created Sales Order #2: Nairobi Grocers Co-op — 700 kg Rose (Grade B), delivery 10 Jul 2026'],
            ['days' =>  1, 'user' => $sales,      'action' => 'updated', 'subject' => SalesOrder::where('status','fulfilled')->first(), 'desc' => 'Updated Sales Order #2: status changed to fulfilled — LOT-0004 dispatched to Nairobi CBD'],
            ['days' =>  1, 'user' => $sales,      'action' => 'created', 'subject' => SalesOrder::where('status','pending')->first(), 'desc' => 'Created Sales Order #3: FreshMart Europe Ltd — 500 kg Avocado pending, delivery in 3 days (no stock allocated yet)'],

            // ── Dispatch ─────────────────────────────────────────────────
            ['days' =>  1, 'user' => $driver,     'action' => 'created', 'subject' => Dispatch::where('status','scheduled')->first(), 'desc' => 'Dispatch scheduled: Delivery Truck KBZ-123, route Naivasha → Nairobi JKIA, 11 Jul 2026'],
            ['days' =>  0, 'user' => $driver,     'action' => 'updated', 'subject' => Dispatch::where('status','delivered')->first(), 'desc' => 'Dispatch updated: Nairobi Grocers Co-op order #2 — status changed to delivered (Naivasha → Nairobi CBD)'],
        ];

        foreach ($entries as $e) {
            $subject = $e['subject'];
            ActivityLog::create([
                'user_id'      => $e['user']?->id,
                'action'       => $e['action'],
                'subject_type' => $subject ? $subject::class : null,
                'subject_id'   => $subject?->getKey(),
                'description'  => $e['desc'],
                'properties'   => null,
                'ip_address'   => '41.90.' . rand(1, 254) . '.' . rand(1, 254),
                'created_at'   => Carbon::now()->subDays($e['days'])->subMinutes(rand(0, 120)),
                'updated_at'   => Carbon::now()->subDays($e['days'])->subMinutes(rand(0, 120)),
            ]);
        }
    }
}
