<?php

namespace App\Http\Controllers;

use App\Models\ApprovedEmail;
use App\Models\User;
use App\Services\SmsService;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * User administration: onboarding via the approved-email allowlist, role
 * assignment, and account activation. Gated to the `admin` module
 * (owner + horticulture_manager).
 */
class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();
        $pendingApprovals = ApprovedEmail::with('inviter')
            ->whereNull('registered_at')
            ->latest()
            ->get();
        $roles = User::ROLES;

        return view('users.index', compact('users', 'pendingApprovals', 'roles'));
    }

    /**
     * Onboard someone: add their email to the allowlist with an assigned role.
     * They can then self-register a password.
     */
    public function approveEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('approved_emails', 'email'), Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:20'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ]);

        $phone = SmsService::normalizePhone($validated['phone']);

        if ($phone === null) {
            return back()->withInput()->withErrors([
                'phone' => 'Enter a valid Kenyan phone number (e.g. 0712345678). The person needs it to verify their account by SMS.',
            ]);
        }

        $this->guardSeniorRole($request, $validated['role']);

        $approval = ApprovedEmail::create([
            'email' => strtolower($validated['email']),
            'phone' => $phone,
            'role' => $validated['role'],
            'invited_by' => $request->user()->id,
        ]);

        ActivityLogger::log('approved_email', $approval, "Approved {$approval->email} for registration as {$approval->role}");

        return back()->with('success', "{$approval->email} has been approved. They can now register — an SMS code will be sent to {$phone} to verify them.");
    }

    /**
     * Revoke a pending (not-yet-registered) approval.
     */
    public function revokeApproval(Request $request, ApprovedEmail $approvedEmail)
    {
        if ($approvedEmail->isRegistered()) {
            return back()->with('error', 'This email has already been used to register — deactivate the user instead.');
        }

        if ((User::ROLE_RANK[$approvedEmail->role] ?? PHP_INT_MAX) < $request->user()->rank()) {
            abort(403, 'You cannot revoke an approval for a role senior to your own.');
        }

        $email = $approvedEmail->email;
        $approvedEmail->delete();

        ActivityLogger::log('revoked_approval', null, "Revoked pending approval for {$email}");

        return back()->with('success', "Approval for {$email} has been revoked.");
    }

    /**
     * Set or correct the phone number on a pending approval — used mainly to
     * fix approvals created before phone became required, so the person can
     * receive their registration OTP without being revoked and re-approved.
     */
    public function updateApprovalPhone(Request $request, ApprovedEmail $approvedEmail)
    {
        if ($approvedEmail->isRegistered()) {
            return back()->with('error', 'This email has already been used to register — update the user\'s phone instead.');
        }

        if ((User::ROLE_RANK[$approvedEmail->role] ?? PHP_INT_MAX) < $request->user()->rank()) {
            abort(403, 'You cannot change an approval for a role senior to your own.');
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = SmsService::normalizePhone($validated['phone']);

        if ($phone === null) {
            return back()->withErrors([
                'phone' => 'Enter a valid Kenyan phone number (e.g. 0712345678).',
            ]);
        }

        $approvedEmail->update(['phone' => $phone]);

        ActivityLogger::log('updated_approval', $approvedEmail, "Set phone for pending approval {$approvedEmail->email} to {$phone}");

        return back()->with('success', "Phone number for {$approvedEmail->email} set to {$phone}.");
    }

    /**
     * Change a user's role.
     */
    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ]);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot change your own role.');
        }

        $this->guardSeniorRole($request, $validated['role'], $user);

        $from = $user->role;
        $user->update(['role' => $validated['role']]);

        ActivityLogger::log('changed_role', $user, "Changed {$user->name}'s role from {$from} to {$validated['role']}");

        return back()->with('success', "{$user->name}'s role updated to {$user->roleLabel()}.");
    }

    /**
     * Activate or deactivate a user's ability to sign in.
     */
    public function toggleActive(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->outranks($request->user())) {
            return back()->with('error', 'You cannot change the account of someone senior to you.');
        }

        $user->update(['is_active' => ! $user->is_active]);
        $action = $user->is_active ? 'activated' : 'deactivated';

        ActivityLogger::log($action, $user, ucfirst($action) . " user {$user->name}");

        return back()->with('success', "{$user->name}'s account has been {$action}.");
    }

    /**
     * Permanently delete a user account (as opposed to deactivating it).
     * Their past activity log entries are kept for the audit trail — the
     * user_id on those rows is set to null, per the migration's
     * nullOnDelete — so history isn't lost, only the login itself.
     */
    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->outranks($request->user())) {
            return back()->with('error', 'You cannot delete the account of someone senior to you.');
        }

        $name = $user->name;
        $email = $user->email;
        $user->delete();

        ActivityLogger::log('deleted_user', null, "Deleted user {$name} ({$email})");

        return back()->with('success', "{$name}'s account has been permanently deleted.");
    }

    /**
     * Reset another user's password. The new password is texted to the user
     * via SMS (Bonga) when a phone number is on file — this is the recovery
     * path for someone who's locked out, since there's no self-service "forgot
     * password" flow. Nobody may reset their own password this way (use
     * updatePassword above, which requires the current one).
     */
    public function resetPassword(Request $request, User $user, SmsService $sms)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot reset your own password this way — use "Change Your Password" below.');
        }

        if ($user->outranks($request->user())) {
            return back()->with('error', 'You cannot reset the password of someone senior to you.');
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $attributes = ['password' => Hash::make($validated['password'])];

        // Let the admin capture/correct the phone number inline so the SMS has
        // somewhere to go, even for users who registered without one.
        if (! empty($validated['phone'])) {
            $attributes['phone'] = SmsService::normalizePhone($validated['phone']) ?? $user->phone;
        }

        $user->update($attributes);

        ActivityLogger::log('reset_password', $user, "Reset {$user->name}'s password");

        // Best-effort SMS; a delivery failure never blocks the reset itself.
        $texted = false;
        if ($user->phone) {
            $message = "Trooms House: your password has been reset by an administrator. "
                . "Your new password is: {$validated['password']} "
                . "Please sign in and keep it private.";
            $texted = $sms->send($user->phone, $message);
        }

        $note = $texted
            ? "The new password has been texted to {$user->phone}."
            : 'Share the new password with them directly'
                . ($user->phone ? ' — the SMS could not be sent.' : ' — no phone number on file to text it to.');

        return back()->with('success', "{$user->name}'s password has been reset. {$note}");
    }

    /**
     * Set, change, or clear a user's phone number. Used to add a number for
     * users who registered before phone was captured, so they can receive
     * password-reset codes. Leaving it blank clears the number.
     */
    public function updateUserPhone(Request $request, User $user)
    {
        if ($user->outranks($request->user())) {
            return back()->with('error', 'You cannot change the account of someone senior to you.');
        }

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        if (empty($validated['phone'])) {
            $user->update(['phone' => null]);
            ActivityLogger::log('updated_user', $user, "Cleared {$user->name}'s phone number");

            return back()->with('success', "{$user->name}'s phone number has been cleared.");
        }

        $phone = SmsService::normalizePhone($validated['phone']);

        if ($phone === null) {
            return back()->withErrors([
                'phone' => 'Enter a valid Kenyan phone number (e.g. 0712345678).',
            ]);
        }

        $user->update(['phone' => $phone]);
        ActivityLogger::log('updated_user', $user, "Set {$user->name}'s phone number to {$phone}");

        return back()->with('success', "{$user->name}'s phone number set to {$phone}.");
    }

    /**
     * Let the signed-in admin change their own password.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        ActivityLogger::log('changed_password', $request->user(), 'Changed their own password');

        return back()->with('success', 'Your password has been updated.');
    }

    /**
     * Prevent privilege escalation: nobody may assign a role senior to their
     * own, and nobody may edit the role of an account that already outranks
     * them (otherwise a junior admin could demote a senior via this endpoint).
     */
    protected function guardSeniorRole(Request $request, string $role, ?User $user = null): void
    {
        $actor = $request->user();

        if ((User::ROLE_RANK[$role] ?? PHP_INT_MAX) < $actor->rank()) {
            abort(403, 'You cannot assign a role senior to your own.');
        }

        if ($user && $user->outranks($actor)) {
            abort(403, 'You cannot change the account of someone senior to you.');
        }
    }
}
