<?php

namespace Tests\Feature;

use App\Models\ApprovedEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalPhoneTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->create(['role' => 'owner', 'is_active' => true]);
    }

    public function test_admin_can_set_phone_on_a_phoneless_pending_approval(): void
    {
        $approval = ApprovedEmail::create(['email' => 'legacy@trooms.house', 'role' => 'agronomist']);
        $this->assertNull($approval->phone);

        $response = $this->actingAs($this->owner())
            ->put(route('users.approvals.phone', $approval), ['phone' => '0712345678']);

        $response->assertSessionHasNoErrors();
        $this->assertSame('254712345678', $approval->fresh()->phone);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $approval = ApprovedEmail::create(['email' => 'legacy@trooms.house', 'role' => 'agronomist']);

        $response = $this->actingAs($this->owner())
            ->from(route('users.index'))
            ->put(route('users.approvals.phone', $approval), ['phone' => 'not-a-number']);

        $response->assertSessionHasErrors('phone');
        $this->assertNull($approval->fresh()->phone);
    }
}
