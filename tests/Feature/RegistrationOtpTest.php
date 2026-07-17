<?php

namespace Tests\Feature;

use App\Models\ApprovedEmail;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationOtpTest extends TestCase
{
    use RefreshDatabase;

    /** Swap in an SMS gateway that "succeeds" without hitting the network. */
    private function fakeSmsSuccess(): void
    {
        $this->app->instance(SmsService::class, new class extends SmsService {
            public function send(?string $phone, string $message): bool
            {
                return true;
            }
        });
    }

    private function approve(string $email, string $phone, string $role = 'agronomist'): ApprovedEmail
    {
        return ApprovedEmail::create([
            'email' => $email,
            'phone' => $phone,
            'role' => $role,
        ]);
    }

    public function test_matching_email_and_phone_sends_otp_and_holds_pending_registration(): void
    {
        $this->fakeSmsSuccess();
        $this->approve('jane@trooms.house', '254712345678');

        $response = $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@trooms.house',
            'phone' => '0712345678', // different format, same number
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('register.verify'));
        $this->assertDatabaseMissing('users', ['email' => 'jane@trooms.house']);

        $pending = session('registration.pending');
        $this->assertNotNull($pending);
        $this->assertSame('254712345678', $pending['phone']);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $pending['otp']);
    }

    public function test_correct_otp_creates_account_and_logs_in(): void
    {
        $this->fakeSmsSuccess();
        $approved = $this->approve('jane@trooms.house', '254712345678');

        $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@trooms.house',
            'phone' => '254712345678',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $otp = session('registration.pending')['otp'];

        $response = $this->post('/register/verify', ['otp' => $otp]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'jane@trooms.house',
            'phone' => '254712345678',
            'role' => 'agronomist',
        ]);
        $this->assertNotNull($approved->fresh()->registered_at);
        $this->assertNull(session('registration.pending'));
    }

    public function test_phone_mismatch_is_rejected_and_no_otp_sent(): void
    {
        $this->fakeSmsSuccess();
        $this->approve('jane@trooms.house', '254712345678');

        $response = $this->from('/register')->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@trooms.house',
            'phone' => '0700000000', // wrong number
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('phone');
        $this->assertNull(session('registration.pending'));
    }

    public function test_wrong_otp_does_not_create_account(): void
    {
        $this->fakeSmsSuccess();
        $this->approve('jane@trooms.house', '254712345678');

        $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@trooms.house',
            'phone' => '254712345678',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response = $this->from('/register/verify')->post('/register/verify', ['otp' => '000000']);

        $response->assertRedirect('/register/verify');
        $response->assertSessionHasErrors('otp');
        $this->assertDatabaseMissing('users', ['email' => 'jane@trooms.house']);
        $this->assertGuest();
    }

    public function test_phoneless_approval_registers_without_otp_and_saves_entered_phone(): void
    {
        $this->fakeSmsSuccess();
        ApprovedEmail::create(['email' => 'legacy@trooms.house', 'role' => 'agronomist']); // no phone

        $response = $this->post('/register', [
            'name' => 'Legacy User',
            'email' => 'legacy@trooms.house',
            'phone' => '0712345678',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'legacy@trooms.house', 'phone' => '254712345678']);
        $this->assertNull(session('registration.pending'));
    }

    public function test_phoneless_approval_registers_even_with_no_phone_entered(): void
    {
        $this->fakeSmsSuccess();
        ApprovedEmail::create(['email' => 'legacy@trooms.house', 'role' => 'agronomist']);

        $response = $this->post('/register', [
            'name' => 'Legacy User',
            'email' => 'legacy@trooms.house',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'legacy@trooms.house', 'phone' => null]);
    }

    public function test_unapproved_email_cannot_register(): void
    {
        $this->fakeSmsSuccess();

        $response = $this->from('/register')->post('/register', [
            'name' => 'Nobody',
            'email' => 'nobody@trooms.house',
            'phone' => '0712345678',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('email');
        $this->assertNull(session('registration.pending'));
    }
}
