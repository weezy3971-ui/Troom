@extends('layouts.app')
@section('title', 'Settings')

@php
    $roleLabel = $user->role === 'owner' ? 'Horticulture Manager' : ucwords(str_replace('_', ' ', $user->role));
@endphp

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Settings</h1>
        <p class="page-subtitle">Your account and workspace</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 320px; gap: 20px; align-items: start;">
    {{-- Profile --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Profile</h3></div>
        <form action="{{ route('settings.profile') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="name">Name *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email *</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="form-input" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Profile</button>
        </form>
    </div>

    {{-- Account summary --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Account</h3></div>
        <div class="detail-item" style="margin-bottom: 12px;">
            <div class="detail-label">Role</div>
            <div class="detail-value">{{ $roleLabel }}</div>
        </div>
        <div class="detail-item" style="margin-bottom: 12px;">
            <div class="detail-label">Farms in scope</div>
            <div class="detail-value">{{ $farmCount }}</div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Member since</div>
            <div class="detail-value">{{ $user->created_at?->format('M d, Y') ?? '—' }}</div>
        </div>
        <form action="{{ route('logout') }}" method="POST" style="margin-top: 16px;">
            @csrf
            <button type="submit" class="btn btn-ghost" style="width: 100%;">Log Out</button>
        </form>
    </div>
</div>
@endsection
