@extends('layouts.app')
@section('title', 'User Administration')

@section('content')
@php
    $actor = auth()->user();
    $isOwner = $actor->isOwner();
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">User Administration</h1>
        <p class="page-subtitle">Onboard people, assign roles, and control who can sign in</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — User Administration">
            <p><strong>Onboard</strong> a user by approving their email and role first — only approved emails can register.</p>
            <p>A <strong>Pending Registration</strong> means the email is approved but the person hasn't created their account yet.</p>
            <p><strong>Deactivate</strong> an account to block sign-in without deleting their history — reactivate any time.</p>
            <p>All role and access changes are recorded in the <strong>Audit Log</strong>.</p>
            <p><strong>Quick steps:</strong> Enter the person's email → pick a role → click "Approve & Onboard" → they'll register themselves from the sign-in page.</p>
        </x-help-panel>
        <a href="{{ route('activity-logs.index') }}" class="btn btn-secondary">View Audit Log</a>
    </div>
</div>

{{-- Onboard: approve an email --}}
<div class="card" style="margin-bottom: 24px; max-width: 820px;">
    <div class="card-header"><h3 class="card-title">Onboard a User</h3></div>
    <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 16px;">
        Approve an email address and assign its role. That person can then create their own account from the sign-in page — no one outside this list can register.
    </p>
    <form action="{{ route('users.approve') }}" method="POST"
          onsubmit="return confirmIfOwnerRole(this, this.email.value || 'this email')">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="email">Email address *</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="person@trooms.co.ke" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="role">Role *</label>
                <select id="role" name="role" class="form-select" required>
                    @foreach($roles as $value => $label)
                        @if((\App\Models\User::ROLE_RANK[$value] ?? PHP_INT_MAX) < $actor->rank()) @continue @endif
                        <option value="{{ $value }}" {{ old('role') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Approve &amp; Onboard</button>
    </form>
</div>

{{-- Pending approvals --}}
@if($pendingApprovals->isNotEmpty())
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header"><h3 class="card-title">Pending Registrations ({{ $pendingApprovals->count() }})</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Email</th><th>Assigned Role</th><th>Approved By</th><th>When</th><th style="text-align:right;">Actions</th></tr></thead>
            <tbody>
                @foreach($pendingApprovals as $approval)
                <tr>
                    <td class="mono">{{ $approval->email }}</td>
                    <td><span class="badge badge-neutral">{{ \App\Models\User::ROLES[$approval->role] ?? $approval->role }}</span></td>
                    <td>{{ $approval->inviter->name ?? '—' }}</td>
                    <td>{{ $approval->created_at->format('M d, Y') }}</td>
                    <td>
                        <div style="display:flex; gap:8px; justify-content:flex-end;">
                            @if((\App\Models\User::ROLE_RANK[$approval->role] ?? PHP_INT_MAX) < $actor->rank())
                                <span style="font-size:12px; color:var(--text-muted);">{{ \App\Models\User::ROLES[$approval->role] ?? $approval->role }}</span>
                            @else
                                <form action="{{ route('users.approvals.revoke', $approval) }}" method="POST" data-confirm="Revoke the pending approval for {{ $approval->email }}?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Revoke</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Existing users --}}
<div class="card">
    <div class="card-header"><h3 class="card-title">Users ({{ $users->count() }})</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th style="text-align:right;">Actions</th></tr></thead>
            <tbody>
                @foreach($users as $user)
                @php
                    $isSelf = $user->id === auth()->id();
                    $targetOutranksActor = $user->outranks($actor);
                    $roleLocked = $isSelf || $targetOutranksActor;
                @endphp
                <tr>
                    <td style="font-weight:600;">{{ $user->name }}</td>
                    <td class="mono">{{ $user->email }}</td>
                    <td>
                        @if($isSelf)
                            <span style="font-size:12px; color:var(--text-muted);">{{ $user->roleLabel() }} (you can't change your own role)</span>
                        @else
                            <form action="{{ route('users.role', $user) }}" method="POST" style="display:flex; gap:6px; align-items:center;"
                                  onsubmit="return confirmIfOwnerRole(this, '{{ addslashes($user->name) }}')">
                                @csrf @method('PUT')
                                <select name="role" class="form-select" style="padding:5px 8px; font-size:12px; min-width:170px;"
                                    {{ $roleLocked ? 'disabled' : '' }}>
                                    @foreach($roles as $value => $label)
                                        @if((\App\Models\User::ROLE_RANK[$value] ?? PHP_INT_MAX) < $actor->rank()) @continue @endif
                                        <option value="{{ $value }}" {{ $user->role === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @unless($roleLocked)
                                    <button type="submit" class="btn btn-ghost btn-sm">Save</button>
                                @endunless
                            </form>
                        @endif
                    </td>
                    <td>
                        @if($user->is_active)
                            <span class="badge badge-active">Active</span>
                        @else
                            <span class="badge badge-down">Deactivated</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap:8px; justify-content:flex-end;">
                            @if($user->id === auth()->id())
                                <span style="font-size:12px; color:var(--text-muted);">You</span>
                            @elseif($targetOutranksActor)
                                <span style="font-size:12px; color:var(--text-muted);">{{ $user->roleLabel() }}</span>
                            @else
                                <form action="{{ route('users.toggle-active', $user) }}" method="POST"
                                      data-confirm="{{ $user->is_active ? 'Deactivate' : 'Reactivate' }} {{ $user->name }}'s account?">
                                    @csrf @method('PUT')
                                    <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-danger' : 'btn-success' }}">
                                        {{ $user->is_active ? 'Deactivate' : 'Reactivate' }}
                                    </button>
                                </form>
                                <button type="button" class="btn btn-secondary btn-sm"
                                        onclick="openResetPasswordModal('{{ route('users.reset-password', $user) }}', '{{ addslashes($user->name) }}')">
                                    Reset Password
                                </button>
                                <form action="{{ route('users.destroy', $user) }}" method="POST"
                                      data-confirm="Permanently delete {{ $user->name }}'s account ({{ $user->email }})? This cannot be undone — they would need to be re-approved and register again from scratch to regain access. Their past activity log entries are kept, but no longer linked to their name. If you just want to block sign-in, use Deactivate instead."
                                      data-confirm-ok="Delete Permanently">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Change your own password --}}
<div class="card" style="margin-top: 24px; max-width: 480px;">
    <div class="card-header"><h3 class="card-title">Change Your Password</h3></div>
    <form action="{{ route('users.password') }}" method="POST">
        @csrf @method('PUT')
        <div class="form-group">
            <label class="form-label" for="current_password">Current password *</label>
            <div class="password-field">
                <input type="password" id="current_password" name="current_password" class="form-input" required autocomplete="current-password">
                <button type="button" class="password-toggle" data-toggle-password="current_password" aria-label="Show password" aria-pressed="false">
                    <span class="icon-eye"><x-icon name="eye" size="16" /></span>
                    <span class="icon-eye-off" style="display:none;"><x-icon name="eye-off" size="16" /></span>
                </button>
            </div>
            @error('current_password')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label class="form-label" for="password">New password *</label>
            <div class="password-field">
                <input type="password" id="password" name="password" class="form-input" minlength="8" required autocomplete="new-password">
                <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password" aria-pressed="false">
                    <span class="icon-eye"><x-icon name="eye" size="16" /></span>
                    <span class="icon-eye-off" style="display:none;"><x-icon name="eye-off" size="16" /></span>
                </button>
            </div>
            @error('password')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label class="form-label" for="password_confirmation">Confirm new password *</label>
            <div class="password-field">
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" minlength="8" required autocomplete="new-password">
                <button type="button" class="password-toggle" data-toggle-password="password_confirmation" aria-label="Show password" aria-pressed="false">
                    <span class="icon-eye"><x-icon name="eye" size="16" /></span>
                    <span class="icon-eye-off" style="display:none;"><x-icon name="eye-off" size="16" /></span>
                </button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Update Password</button>
    </form>
</div>

{{-- Reset-another-user's-password modal (shared across all rows) --}}
<div class="confirm-overlay" id="reset-password-modal" role="dialog" aria-modal="true" aria-labelledby="reset-password-modal-title">
    <div class="confirm-dialog">
        <div class="confirm-icon">🔑</div>
        <div class="confirm-title" id="reset-password-modal-title">Reset Password</div>
        <div class="confirm-message">
            Set a new password for <strong id="reset-password-modal-name"></strong>. You'll need to share it with them directly — there's no email notification.
        </div>
        <form id="reset-password-modal-form" method="POST" action="">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label" for="reset-password-input">New password</label>
                <input type="password" id="reset-password-input" name="password" class="form-input" minlength="8" required autocomplete="new-password">
            </div>
            <div class="form-group">
                <label class="form-label" for="reset-password-confirm-input">Confirm password</label>
                <input type="password" id="reset-password-confirm-input" name="password_confirmation" class="form-input" minlength="8" required autocomplete="new-password">
            </div>
            <div class="confirm-actions">
                <button type="button" class="btn btn-ghost" onclick="closeResetPasswordModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Set New Password</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Grants of the owner (super-admin) role get an extra warning on top of
    // the normal confirm modal, since owner has unrestricted access to
    // everything, including managing other owners.
    function confirmIfOwnerRole(form, who) {
        var roleSelect = form.querySelector('select[name="role"]');
        if (roleSelect && roleSelect.value === 'owner') {
            form.setAttribute('data-confirm', 'Grant SUPERADMIN (owner) access to ' + who + '? Owners have unrestricted access to everything in the system, including managing other owners. This cannot be limited later except by another owner.');
            form.setAttribute('data-confirm-ok', 'Grant Owner Access');
        } else {
            form.removeAttribute('data-confirm');
        }
        return true;
    }

    // Reset-password modal: one shared dialog, populated per-row on open
    // rather than a per-row dropdown, so it can't get clipped by the
    // table's horizontal scroll container and stays centered on any
    // screen size (including phones).
    function openResetPasswordModal(actionUrl, name) {
        var modal = document.getElementById('reset-password-modal');
        var form = document.getElementById('reset-password-modal-form');
        form.action = actionUrl;
        form.reset();
        document.getElementById('reset-password-modal-name').textContent = name;
        modal.classList.add('open');
        document.getElementById('reset-password-input').focus();
    }

    function closeResetPasswordModal() {
        document.getElementById('reset-password-modal').classList.remove('open');
    }

    document.getElementById('reset-password-modal').addEventListener('click', function (e) {
        if (e.target === this) closeResetPasswordModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeResetPasswordModal();
    });
</script>
@endsection
