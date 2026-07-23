<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentVoucherTest extends TestCase
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
            'payment_mode' => 'mpesa',
            'expense_date' => now()->toDateString(),
            'description' => 'NPK and foliar feed',
        ]);
    }

    public function test_issuing_a_voucher_numbers_it_and_shows_the_printable_page(): void
    {
        $expense = $this->expenseWithVendor();

        $this->actingAs($this->financeOfficer())
            ->post(route('expenses.issue-voucher', $expense))
            ->assertRedirect(route('expenses.voucher', $expense));

        $expense->refresh();

        $this->assertSame('PV-'.str_pad((string) $expense->id, 6, '0', STR_PAD_LEFT), $expense->voucher_number);

        $this->actingAs($this->financeOfficer())
            ->get(route('expenses.voucher', $expense))
            ->assertOk()
            ->assertSee($expense->voucher_number)
            ->assertSee('Greenline Agro Supplies')
            ->assertSee('48,000.00');
    }

    public function test_an_expense_with_no_vendor_cannot_be_vouchered(): void
    {
        $expense = Expense::create([
            'category' => 'fuel',
            'amount' => 7200,
            'expense_date' => now()->toDateString(),
            'description' => 'Diesel for the pump',
        ]);

        $this->actingAs($this->financeOfficer())
            ->post(route('expenses.issue-voucher', $expense))
            ->assertRedirect(route('expenses.show', $expense));

        $this->assertNull($expense->fresh()->voucher_number);
    }

    public function test_re_issuing_a_voucher_keeps_the_original_number(): void
    {
        $expense = $this->expenseWithVendor();

        $this->actingAs($this->financeOfficer())->post(route('expenses.issue-voucher', $expense));
        $first = $expense->fresh()->voucher_number;

        $this->actingAs($this->financeOfficer())->post(route('expenses.issue-voucher', $expense));
        $second = $expense->fresh()->voucher_number;

        $this->assertSame($first, $second);
    }

    public function test_the_voucher_page_404s_before_one_has_been_issued(): void
    {
        $expense = $this->expenseWithVendor();

        $this->actingAs($this->financeOfficer())
            ->get(route('expenses.voucher', $expense))
            ->assertNotFound();
    }
}
