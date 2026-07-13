<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\ApprovedEmail;
use App\Models\User;
use App\Support\ActivityLogger;

class AuthController extends Controller
{
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
     * Self-registration is gated by the approved-emails allowlist: a person can
     * only create an account if an administrator has pre-approved their email.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
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

        $user = User::create([
            'name' => $data['name'],
            'email' => $approved->email,
            'password' => Hash::make($data['password']),
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

    public function logout(Request $request)
    {
        ActivityLogger::log('signed_out', Auth::user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
