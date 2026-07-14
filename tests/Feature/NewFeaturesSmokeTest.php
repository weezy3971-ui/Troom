<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\CropProgram;
use App\Models\Customer;
use App\Models\Farm;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewFeaturesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('secret'),
            'role' => 'owner',
        ]);
    }

    public function test_new_index_pages_render(): void
    {
        $this->actingAs($this->owner());

        foreach ([
            '/worker-attendances',
            '/crop-programs',
            '/procurement-requests',
            '/outgrowers',
            '/labour-attendances/create',
            '/crop-programs/create',
            '/procurement-requests/create',
            '/outgrowers/create',
            '/workers/create',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_crop_program_stage_and_schedule_materialisation(): void
    {
        $this->actingAs($this->owner());

        $farm = Farm::create(['name' => 'F', 'location' => 'L', 'size_acres' => 10]);
        $block = Block::create(['farm_id' => $farm->id, 'name' => 'B', 'size_acres' => 2, 'soil_type' => 'loam']);
        $crop = Crop::create(['name' => 'Bean', 'crop_type' => 'Legume']);

        $program = CropProgram::create(['crop_id' => $crop->id, 'name' => 'Std', 'is_active' => true]);
        $program->stages()->create(['sequence' => 1, 'name' => 'First spray', 'activity_type' => 'spraying', 'offset_days' => 7]);
        $program->stages()->create(['sequence' => 2, 'name' => 'Top dressing', 'activity_type' => 'fertigation', 'offset_days' => 14]);

        $cycle = CropCycle::create([
            'block_id' => $block->id,
            'crop_id' => $crop->id,
            'season_name' => 'S1',
            'planting_date' => '2026-01-01',
            'status' => 'planned',
        ]);

        $created = $cycle->materialiseSchedule();
        $this->assertSame(2, $created);
        $this->assertSame('2026-01-08', $cycle->stages()->orderBy('due_date')->first()->due_date->toDateString());

        // Show page renders with the schedule.
        $this->get("/crop-cycles/{$cycle->id}")->assertOk()->assertSee('First spray');
    }

    public function test_labour_target_pay_computes_cost(): void
    {
        $this->actingAs($this->owner());

        $resp = $this->post('/labour-attendances', [
            'attendance_date' => '2026-07-14',
            'worker_name' => 'Casual A',
            'task' => 'Weeding',
            'pay_basis' => 'target',
            'target_unit' => 'beds',
            'qty_completed' => 5,
            'rate_per_unit' => 120,
        ]);
        $resp->assertRedirect();

        $this->assertDatabaseHas('labour_attendances', [
            'worker_name' => 'Casual A',
            'pay_basis' => 'target',
            'cost' => 600.00,
        ]);
    }

    public function test_procurement_receive_posts_inventory(): void
    {
        $this->actingAs($this->owner());
        $farm = Farm::create(['name' => 'F', 'location' => 'L', 'size_acres' => 10]);
        $item = \App\Models\InventoryItem::create([
            'farm_id' => $farm->id, 'name' => 'Fertiliser', 'category' => 'fertilizer',
            'stage' => 'pre_harvest_input', 'unit' => 'kg', 'reorder_level' => 10,
        ]);

        $pr = \App\Models\ProcurementRequest::create(['farm_id' => $farm->id, 'status' => 'requested']);
        $pr->lines()->create(['inventory_item_id' => $item->id, 'item_name' => 'Fertiliser', 'quantity' => 50, 'unit' => 'kg', 'estimated_cost' => 5000]);

        $this->post("/procurement-requests/{$pr->id}/receive")->assertRedirect();

        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $item->id, 'type' => 'receipt', 'quantity' => 50.00,
        ]);
        $this->assertSame('received', $pr->fresh()->status);
    }

    public function test_sales_order_outgrower_line_and_delivery(): void
    {
        $this->actingAs($this->owner());
        $customer = Customer::create(['name' => 'Buyer', 'contact_person' => 'X', 'phone' => '1', 'email' => 'b@e.com']);
        $og = \App\Models\Outgrower::create(['name' => 'OG One', 'is_active' => true]);
        $order = SalesOrder::create([
            'customer_id' => $customer->id, 'order_date' => '2026-07-14',
            'requested_quantity' => 100, 'status' => 'pending',
        ]);

        $this->post("/sales-orders/{$order->id}/lines", [
            'source' => 'outgrower', 'outgrower_id' => $og->id, 'quantity' => 40, 'unit_price' => 150,
        ])->assertRedirect();

        $this->assertDatabaseHas('sales_order_lines', [
            'sales_order_id' => $order->id, 'source' => 'outgrower', 'outgrower_id' => $og->id,
        ]);

        $this->post("/sales-orders/{$order->id}/delivery", [
            'delivered_quantity' => 100, 'rejected_quantity' => 5, 'returned_quantity' => 2, 'amount_repaid' => 300,
        ])->assertRedirect();

        $this->assertSame('5.00', $order->fresh()->rejected_quantity);
    }
}
