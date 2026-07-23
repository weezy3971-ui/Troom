<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function salesOfficer(): User
    {
        return User::factory()->create(['role' => 'sales_officer', 'is_active' => true]);
    }

    private function financeOfficer(): User
    {
        return User::factory()->create(['role' => 'finance_officer', 'is_active' => true]);
    }

    /** An order with one 100kg line at KES 50 — a round KES 5,000 owed. */
    private function order(array $attrs = []): SalesOrder
    {
        $customer = Customer::create(['name' => 'Riverside Grocers Ltd', 'phone' => '0708100001']);

        $order = SalesOrder::create(array_merge([
            'customer_id' => $customer->id,
            'order_date' => now()->subDays(3)->toDateString(),
            'requested_quantity' => 100,
            'status' => 'dispatched',
        ], $attrs));

        SalesOrderLine::create([
            'sales_order_id' => $order->id,
            'source' => 'lot',
            'quantity' => 100,
            'unit_price' => 50,
        ]);

        return $order->fresh('lines');
    }

    public function test_invoicing_freezes_the_order_value_as_the_amount_owed(): void
    {
        $order = $this->order();

        $this->actingAs($this->salesOfficer())
            ->post(route('sales-orders.invoice', $order))
            ->assertRedirect(route('sales-orders.show', $order));

        $order->refresh();

        $this->assertSame('INV-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT), $order->invoice_number);
        $this->assertEquals(5000, (float) $order->total_amount);
        $this->assertSame('unpaid', $order->payment_status);
    }

    public function test_an_order_with_no_priced_lines_cannot_be_invoiced(): void
    {
        $customer = Customer::create(['name' => 'Cedar Market Traders']);
        $order = SalesOrder::create([
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'requested_quantity' => 100,
            'status' => 'pending',
        ]);

        $this->actingAs($this->salesOfficer())
            ->post(route('sales-orders.invoice', $order))
            ->assertSessionHasErrors('invoice');

        $this->assertNull($order->fresh()->invoice_number);
    }

    public function test_recording_a_payment_issues_a_receipt_and_settles_the_order(): void
    {
        $order = $this->order();

        $this->actingAs($this->salesOfficer())->post(route('payments.store', $order), [
            'method' => 'mpesa',
            'amount' => 5000,
            'paid_at' => now()->toDateString(),
            'reference' => 'SGH4KLM9XZ',
        ]);

        $payment = Payment::first();
        $order->refresh();

        $this->assertSame('RCT-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT), $payment->receipt_number);
        $this->assertEquals(5000, (float) $order->amount_paid);
        $this->assertSame('paid', $order->payment_status);
        $this->assertEquals(0, $order->balanceDue());

        // Invoicing happens implicitly when the first payment arrives.
        $this->assertNotNull($order->invoice_number);
    }

    public function test_a_part_payment_leaves_the_order_partial_with_a_balance(): void
    {
        $order = $this->order();

        $this->actingAs($this->salesOfficer())->post(route('payments.store', $order), [
            'method' => 'cash',
            'amount' => 2000,
            'paid_at' => now()->toDateString(),
        ]);

        $order->refresh();

        $this->assertSame('partial', $order->payment_status);
        $this->assertEquals(3000, $order->balanceDue());
    }

    public function test_an_mpesa_payment_is_rejected_without_its_transaction_code(): void
    {
        $order = $this->order();

        $this->actingAs($this->salesOfficer())
            ->post(route('payments.store', $order), [
                'method' => 'mpesa',
                'amount' => 5000,
                'paid_at' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('reference');

        $this->assertSame(0, Payment::count());
    }

    public function test_a_payment_larger_than_the_outstanding_balance_is_rejected(): void
    {
        $order = $this->order();

        $this->actingAs($this->salesOfficer())
            ->post(route('payments.store', $order), [
                'method' => 'cash',
                'amount' => 7500,
                'paid_at' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, Payment::count());
    }

    public function test_a_payment_posts_a_balanced_pair_to_the_ledger(): void
    {
        $order = $this->order();

        $this->actingAs($this->salesOfficer())->post(route('payments.store', $order), [
            'method' => 'mpesa',
            'amount' => 5000,
            'paid_at' => now()->toDateString(),
            'reference' => 'SGH4KLM9XZ',
        ]);

        $entries = LedgerEntry::where('reference_type', 'payment')->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(5000, (float) $entries->sum('debit'));
        $this->assertEquals(5000, (float) $entries->sum('credit'));
        // M-Pesa lands in its own float account, not the cash till.
        $this->assertSame('1100', $entries->firstWhere('debit', '>', 0)->account->code);
    }

    public function test_voiding_a_receipt_reverses_the_ledger_and_reopens_the_balance(): void
    {
        $order = $this->order();

        $this->actingAs($this->salesOfficer())->post(route('payments.store', $order), [
            'method' => 'cash',
            'amount' => 5000,
            'paid_at' => now()->toDateString(),
        ]);

        $payment = Payment::first();

        $this->actingAs($this->financeOfficer())
            ->post(route('payments.void', $payment), ['void_reason' => 'Recorded against the wrong order']);

        $payment->refresh();
        $order->refresh();

        $this->assertTrue($payment->isVoided());
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertEquals(5000, $order->balanceDue());

        // Reversed, not erased: all four entries remain and net to zero.
        $entries = LedgerEntry::where('reference_type', 'payment')->get();
        $this->assertCount(4, $entries);
        $this->assertEquals(0, (float) $entries->sum('debit') - (float) $entries->sum('credit'));
    }

    public function test_a_sales_officer_cannot_void_a_receipt(): void
    {
        $order = $this->order();

        $this->actingAs($this->salesOfficer())->post(route('payments.store', $order), [
            'method' => 'cash',
            'amount' => 5000,
            'paid_at' => now()->toDateString(),
        ]);

        $payment = Payment::first();

        $this->actingAs($this->salesOfficer())
            ->post(route('payments.void', $payment), ['void_reason' => 'Changed my mind'])
            ->assertForbidden();

        $this->assertFalse($payment->fresh()->isVoided());
    }

    public function test_the_receipt_shows_the_number_amount_and_remaining_balance(): void
    {
        $order = $this->order();

        $this->actingAs($this->salesOfficer())->post(route('payments.store', $order), [
            'method' => 'cash',
            'amount' => 2000,
            'paid_at' => now()->toDateString(),
        ]);

        $payment = Payment::first();

        $this->actingAs($this->salesOfficer())
            ->get(route('payments.receipt', $payment))
            ->assertOk()
            ->assertSee($payment->receipt_number)
            ->assertSee('2,000.00')
            ->assertSee('3,000.00'); // balance still due
    }

    public function test_a_voided_receipt_says_so_on_its_face(): void
    {
        $order = $this->order();

        $this->actingAs($this->salesOfficer())->post(route('payments.store', $order), [
            'method' => 'cash',
            'amount' => 5000,
            'paid_at' => now()->toDateString(),
        ]);

        $payment = Payment::first();

        $this->actingAs($this->financeOfficer())
            ->post(route('payments.void', $payment), ['void_reason' => 'Duplicate entry']);

        $this->actingAs($this->salesOfficer())
            ->get(route('payments.receipt', $payment))
            ->assertOk()
            ->assertSee('VOID')
            ->assertSee('Duplicate entry')
            ->assertSee('no longer proof of payment');
    }
}
