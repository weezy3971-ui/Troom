<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorTest extends TestCase
{
    use RefreshDatabase;

    private function financeOfficer(): User
    {
        return User::factory()->create(['role' => 'finance_officer', 'is_active' => true]);
    }

    public function test_a_vendor_phone_is_stored_as_an_msisdn_ready_for_payout(): void
    {
        $this->actingAs($this->financeOfficer())->post(route('vendors.store'), [
            'name' => 'Greenline Agro Supplies',
            'type' => 'supplier',
            'phone' => '0712 345 678',
        ]);

        $vendor = Vendor::first();

        $this->assertSame('254712345678', $vendor->phone);
        $this->assertTrue($vendor->isPayable());
    }

    public function test_an_unusable_phone_number_is_rejected_rather_than_silently_dropped(): void
    {
        $this->actingAs($this->financeOfficer())
            ->post(route('vendors.store'), [
                'name' => 'Northgate Transporters',
                'type' => 'transporter',
                'phone' => '12345',
            ])
            ->assertSessionHasErrors('phone');

        $this->assertSame(0, Vendor::count());
    }

    public function test_a_vendor_without_a_phone_is_not_payable_by_mpesa(): void
    {
        $vendor = Vendor::create(['name' => 'Valley Seed Co', 'type' => 'supplier']);

        $this->assertFalse($vendor->isPayable());
    }

    public function test_retiring_a_vendor_deactivates_rather_than_deletes_them(): void
    {
        $vendor = Vendor::create(['name' => 'Sunrise Irrigation', 'type' => 'service_provider', 'phone' => '0712345678']);
        Expense::create([
            'category' => 'irrigation',
            'vendor_id' => $vendor->id,
            'amount' => 12000,
            'expense_date' => now()->toDateString(),
            'description' => 'Pump servicing',
        ]);

        $this->actingAs($this->financeOfficer())->delete(route('vendors.destroy', $vendor));

        $vendor->refresh();

        $this->assertFalse($vendor->is_active);
        $this->assertFalse($vendor->isPayable());
        // The expense keeps its payee.
        $this->assertSame($vendor->id, Expense::first()->vendor_id);
    }
}
