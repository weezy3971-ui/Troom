<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPhoneTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->create(['role' => 'owner', 'is_active' => true]);
    }

    public function test_admin_can_set_a_users_phone(): void
    {
        $user = User::factory()->create(['role' => 'agronomist', 'phone' => null]);

        $response = $this->actingAs($this->owner())
            ->put(route('users.phone', $user), ['phone' => '0712345678']);

        $response->assertSessionHasNoErrors();
        $this->assertSame('254712345678', $user->fresh()->phone);
    }

    public function test_admin_can_clear_a_users_phone(): void
    {
        $user = User::factory()->create(['role' => 'agronomist', 'phone' => '254712345678']);

        $this->actingAs($this->owner())
            ->put(route('users.phone', $user), ['phone' => '']);

        $this->assertNull($user->fresh()->phone);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'agronomist', 'phone' => '254712345678']);

        $response = $this->actingAs($this->owner())
            ->from(route('users.index'))
            ->put(route('users.phone', $user), ['phone' => 'abc']);

        $response->assertSessionHasErrors('phone');
        $this->assertSame('254712345678', $user->fresh()->phone);
    }

    public function test_cannot_edit_phone_of_someone_senior(): void
    {
        $senior = User::factory()->create(['role' => 'owner', 'phone' => '254700000000']);
        $junior = User::factory()->create(['role' => 'agronomist']);

        $this->actingAs($junior)
            ->put(route('users.phone', $senior), ['phone' => '0712345678']);

        $this->assertSame('254700000000', $senior->fresh()->phone);
    }
}
