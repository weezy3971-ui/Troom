<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The demo data is what anyone new sees first, so it is worth knowing it still
 * builds — and that the pages it feeds actually render against it.
 */
class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_it_seeds_orders_in_paid_partial_and_uninvoiced_states(): void
    {
        $statuses = SalesOrder::pluck('payment_status')->sort()->values()->all();

        $this->assertSame(['paid', 'partial', 'unpaid'], $statuses);
        $this->assertSame(2, Payment::count());
    }

    public function test_seeded_payments_carry_real_receipt_numbers_and_balance_the_ledger(): void
    {
        foreach (Payment::all() as $payment) {
            $this->assertMatchesRegularExpression('/^RCT-\d{6}$/', $payment->receipt_number);
        }

        $partial = SalesOrder::where('payment_status', 'partial')->first();
        $this->assertGreaterThan(0, $partial->balanceDue());

        $this->assertEquals(
            (float) LedgerEntry::sum('debit'),
            (float) LedgerEntry::sum('credit'),
        );
    }

    public function test_every_seeded_phone_number_is_a_valid_msisdn(): void
    {
        $numbers = Customer::pluck('phone')
            ->merge(Vendor::pluck('phone'))
            ->filter();

        $this->assertNotEmpty($numbers);

        foreach ($numbers as $number) {
            $this->assertMatchesRegularExpression('/^254[17]\d{8}$/', $number);
        }
    }

    public function test_the_payment_pages_render_against_the_seeded_data(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $this->actingAs($owner)->get(route('payments.index'))->assertOk();
        $this->actingAs($owner)->get(route('vendors.index'))->assertOk();
        $this->actingAs($owner)->get(route('vendors.create'))->assertOk();
        $this->actingAs($owner)->get(route('expenses.create'))->assertOk();
        $this->actingAs($owner)->get(route('customers.create'))->assertOk();

        // Each order state exercises a different branch of the payments panel:
        // uninvoiced shows the invoice prompt, partial the payment form, paid
        // the settled badge.
        foreach (SalesOrder::all() as $order) {
            $this->actingAs($owner)->get(route('sales-orders.show', $order))->assertOk();
        }

        foreach (Payment::all() as $payment) {
            $this->actingAs($owner)->get(route('payments.receipt', $payment))->assertOk();
        }
    }
}
