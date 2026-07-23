<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MpesaPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_transaction_log_and_simulate_form_render_with_and_without_data(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $this->actingAs($owner)->get(route('mpesa.index'))->assertOk();
        $this->actingAs($owner)->get(route('mpesa.simulate-c2b.form'))->assertOk();

        $vendor = Vendor::create(['name' => 'Greenline Agro Supplies', 'type' => 'supplier', 'phone' => '0712345678']);
        $expense = Expense::create([
            'category' => 'fertilizer',
            'vendor_id' => $vendor->id,
            'amount' => 48000,
            'expense_date' => now()->toDateString(),
            'description' => 'NPK and foliar feed',
        ]);
        $this->actingAs($owner)->post(route('expenses.disburse', $expense));

        $customer = Customer::create(['name' => 'Riverside Grocers Ltd', 'phone' => '0708100001']);
        $order = SalesOrder::create([
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'requested_quantity' => 100,
            'status' => 'dispatched',
        ]);
        SalesOrderLine::create(['sales_order_id' => $order->id, 'source' => 'lot', 'quantity' => 100, 'unit_price' => 50]);
        $this->actingAs($owner)->post(route('mpesa.simulate-c2b'), [
            'phone' => '0708100002',
            'amount' => 1000,
            'account_reference' => 'NOT-A-REAL-INVOICE',
        ]);

        $response = $this->actingAs($owner)->get(route('mpesa.index'));
        $response->assertOk()
            ->assertSee('B2C')
            ->assertSee('C2B')
            ->assertSee('Unallocated');

        $this->actingAs($owner)->get(route('expenses.show', $expense))
            ->assertOk()
            ->assertSee('M-Pesa Receipt');
    }
}
