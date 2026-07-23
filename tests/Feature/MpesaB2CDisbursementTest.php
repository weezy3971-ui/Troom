<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\MpesaTransaction;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MpesaB2CDisbursementTest extends TestCase
{
    use RefreshDatabase;

    private function financeOfficer(): User
    {
        return User::factory()->create(['role' => 'finance_officer', 'is_active' => true]);
    }

    private function expenseWithVendor(): Expense
    {
        $vendor = Vendor::create(['name' => 'Greenline Agro Supplies', 'type' => 'supplier', 'phone' => '0712345678']);

        return Expense::create([
            'category' => 'fertilizer',
            'vendor_id' => $vendor->id,
            'amount' => 48000,
            'expense_date' => now()->toDateString(),
            'description' => 'NPK and foliar feed',
        ]);
    }

    public function test_disbursing_pays_the_vendor_and_issues_a_voucher(): void
    {
        $expense = $this->expenseWithVendor();

        $this->actingAs($this->financeOfficer())
            ->post(route('expenses.disburse', $expense))
            ->assertRedirect(route('expenses.show', $expense));

        $expense->refresh();

        $this->assertSame('mpesa', $expense->payment_mode);
        $this->assertNotNull($expense->voucher_number);
        $this->assertTrue($expense->isDisbursed());

        $transaction = MpesaTransaction::first();
        $this->assertSame('b2c', $transaction->direction);
        $this->assertSame('success', $transaction->status);
        $this->assertSame('254712345678', $transaction->phone);
        $this->assertNotNull($transaction->mpesa_receipt_number);
        $this->assertEquals(48000, (float) $transaction->amount);
    }

    public function test_a_vendor_with_no_phone_cannot_be_disbursed_to(): void
    {
        $vendor = Vendor::create(['name' => 'No Phone Vendor', 'type' => 'supplier']);
        $expense = Expense::create([
            'category' => 'transport',
            'vendor_id' => $vendor->id,
            'amount' => 5000,
            'expense_date' => now()->toDateString(),
            'description' => 'Delivery run',
        ]);

        $this->actingAs($this->financeOfficer())->post(route('expenses.disburse', $expense));

        $this->assertSame(0, MpesaTransaction::count());
        $this->assertNull($expense->fresh()->voucher_number);
    }

    public function test_an_already_disbursed_expense_cannot_be_disbursed_twice(): void
    {
        $expense = $this->expenseWithVendor();

        $this->actingAs($this->financeOfficer())->post(route('expenses.disburse', $expense));
        $this->actingAs($this->financeOfficer())->post(route('expenses.disburse', $expense));

        $this->assertSame(1, MpesaTransaction::count());
    }

    public function test_a_sales_officer_cannot_disburse(): void
    {
        $expense = $this->expenseWithVendor();
        $salesOfficer = User::factory()->create(['role' => 'sales_officer', 'is_active' => true]);

        $this->actingAs($salesOfficer)
            ->post(route('expenses.disburse', $expense))
            ->assertForbidden();

        $this->assertSame(0, MpesaTransaction::count());
    }
}
