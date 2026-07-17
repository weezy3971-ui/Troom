<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetOtpTest extends TestCase
{
    use RefreshDatabase;

    private function fakeSmsSuccess(): void
    {
        $this->app->instance(SmsService::class, new class extends SmsService {
            public function send(?string $phone, string $message): bool
            {
                return true;
            }
        });
    }

    private function userWithPhone(string $phone = '254712345678', bool $active = true): User
    {
        return User::factory()->create([
            'email' => 'jane@trooms.house',
            'phone' => $phone,
            'password' => Hash::make('oldpassword'),
            'is_active' => $active,
        ]);
    }

    public function test_matching_email_and_phone_sends_otp(): void
    {
        $this->fakeSmsSuccess();
        $this->userWithPhone();

        $response = $this->post('/forgot-password', [
            'email' => 'jane@trooms.house',
            'phone' => '0712345678',
        ]);

        $response->assertRedirect(route('password.reset.verify'));
        $pending = session('password_reset.pending');
        $this->assertNotNull($pending);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $pending['otp']);
    }

    public function test_correct_otp_resets_password(): void
    {
        $this->fakeSmsSuccess();
        $user = $this->userWithPhone();

        $this->post('/forgot-password', ['email' => 'jane@trooms.house', 'phone' => '254712345678']);
        $otp = session('password_reset.pending')['otp'];

        $response = $this->post('/forgot-password/verify', [
            'otp' => $otp,
            'password' => 'brandnewpass',
            'password_confirmation' => 'brandnewpass',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('brandnewpass', $user->fresh()->password));
        $this->assertNull(session('password_reset.pending'));
        $this->assertGuest();
    }

    public function test_wrong_phone_is_rejected_and_no_otp(): void
    {
        $this->fakeSmsSuccess();
        $this->userWithPhone();

        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'email' => 'jane@trooms.house',
            'phone' => '0700000000',
        ]);

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHasErrors('email');
        $this->assertNull(session('password_reset.pending'));
    }

    public function test_deactivated_account_cannot_reset(): void
    {
        $this->fakeSmsSuccess();
        $this->userWithPhone(active: false);

        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'email' => 'jane@trooms.house',
            'phone' => '254712345678',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertNull(session('password_reset.pending'));
    }

    public function test_wrong_otp_does_not_change_password(): void
    {
        $this->fakeSmsSuccess();
        $user = $this->userWithPhone();

        $this->post('/forgot-password', ['email' => 'jane@trooms.house', 'phone' => '254712345678']);

        $response = $this->from('/forgot-password/verify')->post('/forgot-password/verify', [
            'otp' => '000000',
            'password' => 'brandnewpass',
            'password_confirmation' => 'brandnewpass',
        ]);

        $response->assertRedirect('/forgot-password/verify');
        $response->assertSessionHasErrors('otp');
        $this->assertTrue(Hash::check('oldpassword', $user->fresh()->password));
    }
}
