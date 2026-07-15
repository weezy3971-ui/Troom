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

        $this->guardOwnerRole($request, $validated['role']);

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
    public function revokeApproval(ApprovedEmail $approvedEmail)
    {
        if ($approvedEmail->isRegistered()) {
            return back()->with('error', 'This email has already been used to register — deactivate the user instead.');
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

        $this->guardOwnerRole($request, $validated['role'], $user);

        if ($user->id === $request->user()->id && $user->role === 'owner' && $validated['role'] !== 'owner') {
            return back()->with('error', 'You cannot remove your own owner role.');
        }

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

        if ($user->role === 'owner' && ! $request->user()->isOwner()) {
            return back()->with('error', 'Only an owner can change an owner account.');
        }

        $user->update(['is_active' => ! $user->is_active]);
        $action = $user->is_active ? 'activated' : 'deactivated';

        ActivityLogger::log($action, $user, ucfirst($action) . " user {$user->name}");

        return back()->with('success', "{$user->name}'s account has been {$action}.");
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
     * Prevent privilege escalation: only an owner may grant the owner role.
     */
    protected function guardOwnerRole(Request $request, string $role, ?User $user = null): void
    {
        if ($role === 'owner' && ! $request->user()->isOwner()) {
            abort(403, 'Only an owner can assign the owner role.');
        }
    }
}
