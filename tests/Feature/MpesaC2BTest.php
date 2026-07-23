<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\MpesaWebhookController;
use App\Models\Customer;
use App\Models\MpesaTransaction;
use App\Models\Payment;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MpesaC2BTest extends TestCase
{
    use RefreshDatabase;

    private function salesOfficer(): User
    {
        return User::factory()->create(['role' => 'sales_officer', 'is_active' => true]);
    }

    private function invoicedOrder(): SalesOrder
    {
        $customer = Customer::create(['name' => 'Riverside Grocers Ltd', 'phone' => '0708100001']);
        $order = SalesOrder::create([
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'requested_quantity' => 100,
            'status' => 'dispatched',
        ]);
        SalesOrderLine::create([
            'sales_order_id' => $order->id,
            'source' => 'lot',
            'quantity' => 100,
            'unit_price' => 50,
        ]);
        $order->load('lines');
        $order->issueInvoice();

        return $order;
    }

    public function test_a_c2b_payment_with_a_matching_reference_settles_the_order_and_issues_a_receipt(): void
    {
        $order = $this->invoicedOrder();

        $this->actingAs($this->salesOfficer())->post(route('mpesa.simulate-c2b'), [
            'phone' => '0708100001',
            'amount' => 5000,
            'account_reference' => $order->invoice_number,
        ]);

        $order->refresh();

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(1, Payment::count());
        $this->assertSame('mpesa', Payment::first()->method);

        $transaction = MpesaTransaction::first();
        $this->assertSame('c2b', $transaction->direction);
        $this->assertSame(SalesOrder::class, $transaction->payable_type);
        $this->assertSame($order->id, $transaction->payable_id);
    }

    public function test_a_c2b_payment_with_no_matching_reference_is_held_unallocated(): void
    {
        $this->actingAs($this->salesOfficer())->post(route('mpesa.simulate-c2b'), [
            'phone' => '0799999999',
            'amount' => 3000,
            'account_reference' => 'NOT-A-REAL-INVOICE',
        ]);

        $transaction = MpesaTransaction::first();

        $this->assertTrue($transaction->isUnallocated());
        $this->assertSame(0, Payment::count());
    }

    public function test_the_real_confirmation_webhook_uses_the_same_matching_logic(): void
    {
        $order = $this->invoicedOrder();

        $response = $this->postJson(route('api.mpesa.c2b.confirmation'), [
            'MSISDN' => '254708100001',
            'TransAmount' => '5000',
            'BillRefNumber' => $order->invoice_number,
            'TransID' => 'SGH4KLM9XZ',
        ]);

        $response->assertOk()->assertJson(['ResultCode' => 0]);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('SGH4KLM9XZ', Payment::first()->reference);
    }

    public function test_the_validation_webhook_always_accepts(): void
    {
        $this->postJson(route('api.mpesa.c2b.validation'), [])
            ->assertOk()
            ->assertJson(['ResultCode' => 0]);
    }
}
