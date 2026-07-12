@extends('layouts.app')
@section('title', 'User Administration')

@section('content')
@php $isOwner = auth()->user()->isOwner(); @endphp

<div class="page-header">
    <div>
        <h1 class="page-title">User Administration</h1>
        <p class="page-subtitle">Onboard people, assign roles, and control who can sign in</p>
    </div>
    <a href="{{ route('activity-logs.index') }}" class="btn btn-secondary">View Audit Log</a>
</div>

{{-- Onboard: approve an email --}}
<div class="card" style="margin-bottom: 24px; max-width: 820px;">
    <div class="card-header"><h3 class="card-title">Onboard a User</h3></div>
    <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 16px;">
        Approve an email address and assign its role. That person can then create their own account from the sign-in page — no one outside this list can register.
    </p>
    <form action="{{ route('users.approve') }}" method="POST">
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
                        @if($value === 'owner' && !$isOwner) @continue @endif
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
                            <form action="{{ route('users.approvals.revoke', $approval) }}" method="POST" data-confirm="Revoke the pending approval for {{ $approval->email }}?">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Revoke</button>
                            </form>
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
                <tr>
                    <td style="font-weight:600;">{{ $user->name }}</td>
                    <td class="mono">{{ $user->email }}</td>
                    <td>
                        <form action="{{ route('users.role', $user) }}" method="POST" style="display:flex; gap:6px; align-items:center;">
                            @csrf @method('PUT')
                            <select name="role" class="form-select" style="padding:5px 8px; font-size:12px; min-width:170px;"
                                {{ ($user->role === 'owner' && !$isOwner) ? 'disabled' : '' }}>
                                @foreach($roles as $value => $label)
                                    @if($value === 'owner' && !$isOwner) @continue @endif
                                    <option value="{{ $value }}" {{ $user->role === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @unless($user->role === 'owner' && !$isOwner)
                                <button type="submit" class="btn btn-ghost btn-sm">Save</button>
                            @endunless
                        </form>
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
                            @elseif($user->role === 'owner' && !$isOwner)
                                <span style="font-size:12px; color:var(--text-muted);">Owner</span>
                            @else
                                <form action="{{ route('users.toggle-active', $user) }}" method="POST"
                                      data-confirm="{{ $user->is_active ? 'Deactivate' : 'Reactivate' }} {{ $user->name }}'s account?">
                                    @csrf @method('PUT')
                                    <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-danger' : 'btn-success' }}">
                                        {{ $user->is_active ? 'Deactivate' : 'Reactivate' }}
                                    </button>
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
@endsection
