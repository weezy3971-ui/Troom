<?php

namespace App\Http\Controllers;

use App\Models\ApprovedEmail;
use App\Models\User;
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
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ]);

        $this->guardSeniorRole($request, $validated['role']);

        $approval = ApprovedEmail::create([
            'email' => strtolower($validated['email']),
            'role' => $validated['role'],
            'invited_by' => $request->user()->id,
        ]);

        ActivityLogger::log('approved_email', $approval, "Approved {$approval->email} for registration as {$approval->role}");

        return back()->with('success', "{$approval->email} has been approved. They can now register an account.");
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
     * Reset another user's password — there's no self-service "forgot
     * password" flow (no mail service wired up), so this is the recovery
     * path for someone who's locked out. The new password must be shared
     * with them directly; nobody may reset their own password this way
     * (use updatePassword above, which requires the current one).
     */
    public function resetPassword(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot reset your own password this way — use "Change Your Password" below.');
        }

        if ($user->outranks($request->user())) {
            return back()->with('error', 'You cannot reset the password of someone senior to you.');
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        ActivityLogger::log('reset_password', $user, "Reset {$user->name}'s password");

        return back()->with('success', "{$user->name}'s password has been reset. Share the new password with them directly.");
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
