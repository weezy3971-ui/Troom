<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\ApprovedEmail;
use App\Models\User;
use App\Services\SmsService;
use App\Support\ActivityLogger;

class AuthController extends Controller
{
    /** Session key holding the in-progress (OTP-pending) registration. */
    private const PENDING_KEY = 'registration.pending';

    /** OTP settings. */
    private const OTP_TTL_MINUTES = 10;
    private const OTP_MAX_ATTEMPTS = 5;

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Deactivated accounts keep their record but are denied sign-in.
            if (! Auth::user()->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact your administrator.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            ActivityLogger::log('signed_in', Auth::user());

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Registration step 1. Self-registration is gated by the approved-emails
     * allowlist: a person may only create an account if an administrator has
     * pre-approved their email.
     *
     * If the admin recorded a phone number for that email, we verify identity
     * by SMS: the phone they enter must match, and a one-time code is texted —
     * the account isn't created until the code is confirmed (verifyOtp). If no
     * phone is on file (an older approval made before phone verification), the
     * account is created immediately, with any phone they enter saved for later.
     */
    public function register(Request $request, SmsService $sms)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|confirmed|min:8',
        ]);

        $email = strtolower(trim($data['email']));

        $approved = ApprovedEmail::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $approved) {
            return back()->withInput()->withErrors([
                'email' => 'This email has not been approved for registration. Please contact your administrator.',
            ]);
        }

        if ($approved->isRegistered() || User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return back()->withInput()->withErrors([
                'email' => 'An account for this email already exists. Please sign in instead.',
            ]);
        }

        // Legacy approvals with no phone on file skip SMS verification entirely
        // — the person registers directly, and any phone they type is saved to
        // their new account so they can use SMS features (like password reset)
        // from then on.
        if (empty($approved->phone)) {
            return $this->createRegisteredUser(
                request: $request,
                approved: $approved,
                name: $data['name'],
                password: $data['password'],
                phone: SmsService::normalizePhone($data['phone'] ?? null),
            );
        }

        if (empty($data['phone'])) {
            return back()->withInput()->withErrors([
                'phone' => 'Enter the phone number your administrator registered for you, so we can text you a verification code.',
            ]);
        }

        // The entered phone must match the admin-recorded one — this is what
        // proves "it's really them". Compare in normalized (MSISDN) form so
        // 0712…, +254712…, 254712… all count as equal.
        if (SmsService::normalizePhone($data['phone']) !== $approved->phone) {
            return back()->withInput()->withErrors([
                'phone' => 'This phone number doesn\'t match the one on file for this email. Please check with your administrator.',
            ]);
        }

        $otp = $this->generateOtp();

        if (! $sms->send($approved->phone, $this->otpMessage($otp))) {
            return back()->withInput()->withErrors([
                'phone' => 'We couldn\'t send the verification code right now. Please try again in a moment.',
            ]);
        }

        // Hold the pending registration server-side (session) until the code is
        // confirmed. Password stays here, not in the DB, until the account is
        // actually created; the session is cleared as soon as it is.
        $request->session()->put(self::PENDING_KEY, [
            'name' => $data['name'],
            'email' => $approved->email,
            'phone' => $approved->phone,
            'password' => $data['password'],
            'approved_id' => $approved->id,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES)->timestamp,
            'attempts' => 0,
        ]);

        return redirect()->route('register.verify');
    }

    /**
     * Registration step 2 (show): the OTP entry screen. Only reachable while a
     * pending registration is held in the session.
     */
    public function showVerify(Request $request)
    {
        $pending = $request->session()->get(self::PENDING_KEY);

        if (! $pending) {
            return redirect()->route('register')
                ->withErrors(['email' => 'Start by entering your details to receive a verification code.']);
        }

        return view('auth.verify', [
            'maskedPhone' => self::maskPhone($pending['phone']),
        ]);
    }

    /**
     * Registration step 2 (submit): confirm the texted code, then create the
     * account. The code expires after OTP_TTL_MINUTES and allows a limited
     * number of attempts before the pending registration is discarded.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|string']);

        $pending = $request->session()->get(self::PENDING_KEY);

        if (! $pending) {
            return redirect()->route('register')
                ->withErrors(['email' => 'Your session expired. Please start again.']);
        }

        if (now()->timestamp > $pending['expires_at']) {
            $request->session()->forget(self::PENDING_KEY);

            return redirect()->route('register')
                ->withErrors(['email' => 'That code expired. Please register again to get a new one.']);
        }

        if ($pending['attempts'] >= self::OTP_MAX_ATTEMPTS) {
            $request->session()->forget(self::PENDING_KEY);

            return redirect()->route('register')
                ->withErrors(['email' => 'Too many incorrect codes. Please register again to get a new one.']);
        }

        if (! hash_equals($pending['otp'], trim($request->input('otp')))) {
            $pending['attempts']++;
            $request->session()->put(self::PENDING_KEY, $pending);

            return back()->withErrors([
                'otp' => 'That code is incorrect. ' . (self::OTP_MAX_ATTEMPTS - $pending['attempts']) . ' attempt(s) left.',
            ]);
        }

        // Re-check the allowlist at the last moment, in case the approval was
        // revoked or used while this registration was mid-flight.
        $approved = ApprovedEmail::find($pending['approved_id']);

        if (! $approved || $approved->isRegistered() || User::whereRaw('LOWER(email) = ?', [strtolower($pending['email'])])->exists()) {
            $request->session()->forget(self::PENDING_KEY);

            return redirect()->route('register')
                ->withErrors(['email' => 'This email is no longer available for registration. Please contact your administrator.']);
        }

        $request->session()->forget(self::PENDING_KEY);

        return $this->createRegisteredUser(
            request: $request,
            approved: $approved,
            name: $pending['name'],
            password: $pending['password'],
            phone: $pending['phone'],
        );
    }

    /**
     * Create the account for an approved email, mark the approval used, sign
     * the person in, and send them to the dashboard. Shared by the SMS-verified
     * path (verifyOtp) and the no-phone legacy path (register).
     */
    private function createRegisteredUser(Request $request, ApprovedEmail $approved, string $name, string $password, ?string $phone)
    {
        $user = User::create([
            'name' => $name,
            'email' => $approved->email,
            'phone' => $phone,
            'password' => Hash::make($password),
            'role' => $approved->role,
            'is_active' => true,
        ]);

        $approved->update(['registered_at' => now()]);

        Auth::login($user);
        $request->session()->regenerate();
        ActivityLogger::log('registered', $user);

        return redirect()->route('dashboard')
            ->with('success', 'Welcome, ' . $user->name . '! Your account is ready.');
    }

    /**
     * Re-send the verification code to the same phone, refreshing its expiry.
     */
    public function resendOtp(Request $request, SmsService $sms)
    {
        $pending = $request->session()->get(self::PENDING_KEY);

        if (! $pending) {
            return redirect()->route('register')
                ->withErrors(['email' => 'Your session expired. Please start again.']);
        }

        $otp = $this->generateOtp();

        if (! $sms->send($pending['phone'], $this->otpMessage($otp))) {
            return back()->withErrors(['otp' => 'We couldn\'t resend the code right now. Please try again in a moment.']);
        }

        $pending['otp'] = $otp;
        $pending['expires_at'] = now()->addMinutes(self::OTP_TTL_MINUTES)->timestamp;
        $pending['attempts'] = 0;
        $request->session()->put(self::PENDING_KEY, $pending);

        return back()->with('success', 'A new code has been sent to your phone.');
    }

    /*
    |--------------------------------------------------------------------------
    | Self-service password reset (SMS OTP)
    |--------------------------------------------------------------------------
    | A locked-out user resets their own password: they prove identity with
    | their email + phone on file, confirm a texted code, and set a new
    | password — no administrator involved. Mirrors the registration OTP flow.
    */

    /** Session key holding the in-progress password reset. */
    private const RESET_KEY = 'password_reset.pending';

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Step 1: verify the email + phone belong to an active account, then text
     * a one-time code to that phone.
     */
    public function sendResetOtp(Request $request, SmsService $sms)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
        ]);

        $email = strtolower(trim($data['email']));
        $phone = SmsService::normalizePhone($data['phone']);

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        // Require an active account whose phone matches. Deactivated or
        // phoneless accounts can't self-reset — they must contact an admin.
        if (! $user || ! $user->is_active || empty($user->phone) || $phone === null || $user->phone !== $phone) {
            return back()->withInput()->withErrors([
                'email' => 'We couldn\'t match those details to an active account. Check your email and phone number, or contact your administrator.',
            ]);
        }

        $otp = $this->generateOtp();

        if (! $sms->send($user->phone, $this->otpMessage($otp))) {
            return back()->withInput()->withErrors([
                'email' => 'We couldn\'t send the verification code right now. Please try again in a moment.',
            ]);
        }

        $request->session()->put(self::RESET_KEY, [
            'user_id' => $user->id,
            'phone' => $user->phone,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES)->timestamp,
            'attempts' => 0,
        ]);

        return redirect()->route('password.reset.verify');
    }

    public function showResetVerify(Request $request)
    {
        $pending = $request->session()->get(self::RESET_KEY);

        if (! $pending) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Start by entering your email and phone to receive a code.']);
        }

        return view('auth.forgot-password-verify', [
            'maskedPhone' => self::maskPhone($pending['phone']),
        ]);
    }

    /**
     * Step 2: confirm the code and set the new password.
     */
    public function resetWithOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string',
            'password' => 'required|confirmed|min:8',
        ]);

        $pending = $request->session()->get(self::RESET_KEY);

        if (! $pending) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Your session expired. Please start again.']);
        }

        if (now()->timestamp > $pending['expires_at']) {
            $request->session()->forget(self::RESET_KEY);

            return redirect()->route('password.request')
                ->withErrors(['email' => 'That code expired. Please request a new one.']);
        }

        if ($pending['attempts'] >= self::OTP_MAX_ATTEMPTS) {
            $request->session()->forget(self::RESET_KEY);

            return redirect()->route('password.request')
                ->withErrors(['email' => 'Too many incorrect codes. Please request a new one.']);
        }

        if (! hash_equals($pending['otp'], trim($request->input('otp')))) {
            $pending['attempts']++;
            $request->session()->put(self::RESET_KEY, $pending);

            return back()->withErrors([
                'otp' => 'That code is incorrect. ' . (self::OTP_MAX_ATTEMPTS - $pending['attempts']) . ' attempt(s) left.',
            ]);
        }

        $user = User::find($pending['user_id']);

        if (! $user || ! $user->is_active) {
            $request->session()->forget(self::RESET_KEY);

            return redirect()->route('password.request')
                ->withErrors(['email' => 'This account is no longer available. Please contact your administrator.']);
        }

        $user->update(['password' => Hash::make($request->input('password'))]);

        $request->session()->forget(self::RESET_KEY);

        ActivityLogger::log('reset_password', $user, 'Reset their own password via SMS verification');

        return redirect()->route('login')
            ->with('success', 'Your password has been reset. Please sign in with your new password.');
    }

    public function resendResetOtp(Request $request, SmsService $sms)
    {
        $pending = $request->session()->get(self::RESET_KEY);

        if (! $pending) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Your session expired. Please start again.']);
        }

        $otp = $this->generateOtp();

        if (! $sms->send($pending['phone'], $this->otpMessage($otp))) {
            return back()->withErrors(['otp' => 'We couldn\'t resend the code right now. Please try again in a moment.']);
        }

        $pending['otp'] = $otp;
        $pending['expires_at'] = now()->addMinutes(self::OTP_TTL_MINUTES)->timestamp;
        $pending['attempts'] = 0;
        $request->session()->put(self::RESET_KEY, $pending);

        return back()->with('success', 'A new code has been sent to your phone.');
    }

    /** Generate a 6-digit one-time code. */
    private function generateOtp(): string
    {
        return (string) random_int(100000, 999999);
    }

    /** The SMS body carrying a one-time code. */
    private function otpMessage(string $otp): string
    {
        return "Your Trooms House verification code is {$otp}. "
            . 'It expires in ' . self::OTP_TTL_MINUTES . ' minutes. '
            . 'Do not share this code with anyone.';
    }

    /**
     * Mask a phone number for display, e.g. 254712345678 -> 2547****5678.
     */
    private static function maskPhone(string $phone): string
    {
        if (strlen($phone) <= 8) {
            return $phone;
        }

        return substr($phone, 0, 4) . str_repeat('*', strlen($phone) - 8) . substr($phone, -4);
    }

    public function logout(Request $request)
    {
        ActivityLogger::log('signed_out', Auth::user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
