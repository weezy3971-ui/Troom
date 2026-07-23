<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Farm;
use App\Models\HarvestBatch;
use App\Models\Outgrower;
use App\Models\PackhouseLot;
use App\Models\QualityCheck;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\Vendor;
use App\Models\Worker;
use App\Support\PaymentPosting;
use Illuminate\Database\Seeder;

/**
 * Sample data for demos and local work.
 *
 * Every name here is invented and generic on purpose — no real customer,
 * supplier, worker, or farm from the live operation appears in source control.
 * Phone numbers are in Safaricom's 254708/254799 test-friendly ranges rather
 * than anyone's actual line, so a demo can never text or pay a real person.
 *
 * Run standalone with:  php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $farm = Farm::create([
            'name' => 'Main Estate Farm',
            'location' => 'Central Region',
            'size_acres' => 42.5,
        ]);

        $blocks = collect(['Block A', 'Block B', 'Block C'])->map(fn ($name, $i) => Block::create([
            'farm_id' => $farm->id,
            'name' => $name,
            'size_acres' => [12.0, 9.5, 8.0][$i],
            'soil_type' => ['Loam', 'Clay loam', 'Sandy loam'][$i],
        ]));

        $crops = collect([
            ['name' => 'Tomato', 'variety' => 'Roma', 'crop_type' => 'vegetable', 'days_to_maturity' => 90, 'expected_yield_per_acre' => 12000],
            ['name' => 'French Beans', 'variety' => 'Star 2054', 'crop_type' => 'vegetable', 'days_to_maturity' => 60, 'expected_yield_per_acre' => 4500],
            ['name' => 'Capsicum', 'variety' => 'California Wonder', 'crop_type' => 'vegetable', 'days_to_maturity' => 75, 'expected_yield_per_acre' => 9000],
        ])->map(fn ($c) => Crop::create($c));

        // ---- Customers — the buyers produce is invoiced to ----
        $customers = collect([
            ['name' => 'Riverside Grocers Ltd', 'contact' => 'Procurement desk', 'phone' => '0708100001', 'price_list' => 'Wholesale 2026'],
            ['name' => 'Cedar Market Traders', 'contact' => 'Buying office', 'phone' => '0708100002', 'price_list' => 'Wholesale 2026'],
            ['name' => 'Blue Harbour Exporters', 'contact' => 'Export desk', 'phone' => '0708100003', 'price_list' => 'Export 2026'],
        ])->map(fn ($c) => Customer::create($c));

        // ---- Vendors — who the farm pays ----
        collect([
            ['name' => 'Greenline Agro Supplies', 'type' => 'supplier', 'phone' => '0799200001'],
            ['name' => 'Valley Seed & Seedling Co', 'type' => 'supplier', 'phone' => '0799200002'],
            ['name' => 'Northgate Transporters', 'type' => 'transporter', 'phone' => '0799200003'],
            ['name' => 'Sunrise Irrigation Services', 'type' => 'service_provider', 'phone' => '0799200004'],
        ])->each(fn ($v) => Vendor::create($v));

        collect([
            ['name' => 'Field Team Lead', 'phone' => '0799300001', 'default_rate' => 700],
            ['name' => 'Harvest Hand 1', 'phone' => '0799300002', 'default_rate' => 500],
            ['name' => 'Harvest Hand 2', 'phone' => '0799300003', 'default_rate' => 500],
            ['name' => 'Packhouse Hand 1', 'phone' => '0799300004', 'default_rate' => 550],
        ])->each(fn ($w) => Worker::create($w));

        collect([
            ['name' => 'Hillside Smallholder Group', 'phone' => '0799400001', 'location' => 'Eastern Ridge'],
            ['name' => 'Lakeside Growers Co-op', 'phone' => '0799400002', 'location' => 'Lower Valley'],
        ])->each(fn ($o) => Outgrower::create($o));

        // ---- One crop cycle carried all the way through to a paid invoice,
        // so the payments and receipting flow has real rows behind it ----
        $cycle = CropCycle::create([
            'block_id' => $blocks[0]->id,
            'crop_id' => $crops[0]->id,
            'season_name' => 'Season 1 2026',
            'planting_date' => now()->subDays(95)->toDateString(),
            'expected_harvest_date' => now()->subDays(5)->toDateString(),
            'status' => 'active',
        ]);

        $lots = collect();

        foreach ([[now()->subDays(12), 1800.0], [now()->subDays(6), 2400.0]] as $i => [$date, $kg]) {
            $batch = HarvestBatch::create([
                'crop_cycle_id' => $cycle->id,
                'block_id' => $blocks[0]->id,
                'harvest_date' => $date->toDateString(),
                'quantity_kg' => $kg,
                'quality_grade' => 'Grade A',
                'rejects_kg' => round($kg * 0.04, 2),
            ]);

            $lot = PackhouseLot::create([
                'harvest_batch_id' => $batch->id,
                'lot_number' => 'LOT-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'pack_date' => $date->copy()->addDay()->toDateString(),
                'quantity_packed' => round($kg * 0.96, 2),
                'packaging_type' => 'Crate 20kg',
                'traceability_code' => 'TRC-2026-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
            ]);

            // Lines can only be allocated from quality-passed lots.
            QualityCheck::create([
                'packhouse_lot_id' => $lot->id,
                'check_date' => $lot->pack_date,
                'result' => 'pass',
            ]);

            $lots->push($lot);
        }

        // Order 1 — invoiced and settled in full by M-Pesa.
        $paidOrder = SalesOrder::create([
            'customer_id' => $customers[0]->id,
            'crop_id' => $crops[0]->id,
            'order_date' => now()->subDays(10)->toDateString(),
            'requested_quantity' => 1700,
            'status' => 'fulfilled',
            'delivery_date' => now()->subDays(8)->toDateString(),
            'delivered_quantity' => 1700,
        ]);

        SalesOrderLine::create([
            'sales_order_id' => $paidOrder->id,
            'packhouse_lot_id' => $lots[0]->id,
            'source' => 'lot',
            'quantity' => 1700,
            'unit_price' => 85,
        ]);

        $this->settle($paidOrder, [
            ['amount' => 144500, 'method' => 'mpesa', 'reference' => 'SGH4KLM9XZ', 'days_ago' => 8],
        ]);

        // Order 2 — part-paid, so the "partial" state and a balance due are
        // both visible without anyone having to construct them by hand.
        $partOrder = SalesOrder::create([
            'customer_id' => $customers[1]->id,
            'crop_id' => $crops[0]->id,
            'order_date' => now()->subDays(5)->toDateString(),
            'requested_quantity' => 2200,
            'status' => 'dispatched',
            'delivery_date' => now()->subDays(3)->toDateString(),
            'delivered_quantity' => 2200,
        ]);

        SalesOrderLine::create([
            'sales_order_id' => $partOrder->id,
            'packhouse_lot_id' => $lots[1]->id,
            'source' => 'lot',
            'quantity' => 2200,
            'unit_price' => 82,
        ]);

        $this->settle($partOrder, [
            ['amount' => 100000, 'method' => 'mpesa', 'reference' => 'SGJ7PQR2AB', 'days_ago' => 3],
        ]);

        // Order 3 — raised but not yet invoiced, the state most orders sit in.
        $openOrder = SalesOrder::create([
            'customer_id' => $customers[2]->id,
            'crop_id' => $crops[1]->id,
            'order_date' => now()->subDay()->toDateString(),
            'requested_quantity' => 900,
            'status' => 'pending',
            'delivery_date' => now()->addDays(4)->toDateString(),
        ]);

        SalesOrderLine::create([
            'sales_order_id' => $openOrder->id,
            'source' => 'lot',
            'quantity' => 900,
            'unit_price' => 130,
        ]);

        // ---- Spend, with a payee on it ----
        $supplier = Vendor::where('name', 'Greenline Agro Supplies')->first();
        $transporter = Vendor::where('name', 'Northgate Transporters')->first();

        collect([
            ['category' => 'fertilizer', 'vendor_id' => $supplier->id, 'amount' => 48000, 'payment_mode' => 'mpesa', 'description' => 'NPK and foliar feed for Block A'],
            ['category' => 'transport', 'vendor_id' => $transporter->id, 'amount' => 15500, 'payment_mode' => 'mpesa', 'description' => 'Delivery run to Riverside Grocers'],
            ['category' => 'fuel', 'vendor_id' => null, 'amount' => 7200, 'payment_mode' => 'cash', 'description' => 'Diesel for the irrigation pump'],
        ])->each(function ($e, $i) use ($farm, $blocks) {
            $expense = Expense::create([
                ...$e,
                'expense_date' => now()->subDays(9 - $i)->toDateString(),
                'farm_id' => $farm->id,
                'block_id' => $blocks[0]->id,
            ]);

            // One expense seeded with its voucher already issued, so the
            // "Paid To" / "View Voucher" flow has something to look at
            // without anyone having to click through it by hand first.
            if ($expense->vendor_id) {
                $expense->issueVoucher();
            }
        });
    }

    /**
     * Invoice an order and record payments against it, through the same model
     * methods the app uses — so the demo data lands with correct receipt
     * numbers, payment status, and ledger entries rather than hand-set values
     * that would drift from whatever the real code does.
     *
     * @param  array<int, array{amount: float|int, method: string, reference: string, days_ago: int}>  $payments
     */
    private function settle(SalesOrder $order, array $payments): void
    {
        $order->load('lines');
        $order->issueInvoice();

        foreach ($payments as $p) {
            $payment = $order->payments()->create([
                'customer_id' => $order->customer_id,
                'method' => $p['method'],
                'amount' => $p['amount'],
                'paid_at' => now()->subDays($p['days_ago'])->toDateString(),
                'reference' => $p['reference'],
            ]);

            PaymentPosting::post($payment);
        }

        $order->refreshPaymentStatus();
    }
}
