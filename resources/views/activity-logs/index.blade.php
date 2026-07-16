@extends('layouts.app')
@section('title', 'Activity Log')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Activity Log</h1>
        <p class="page-subtitle">Business operations performed by your team</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Activity Log">
            <p>Every create, update, delete, and status change across the app is recorded here automatically — there's nothing to set up.</p>
            <p>Use the <strong>filters</strong> above to narrow by team member, action type, or date.</p>
            <p>Sign-ins and other auth events are hidden by default — toggle <strong>"Show all"</strong> to include them.</p>
            <p><strong>Quick steps:</strong> Pick a team member, action, or date in the filter bar → click "Filter" → click "Reset" to clear.</p>
            <p>The list shows <strong>today's activity</strong> by default to stay readable. Nothing is ever deleted — use <strong>View Full History</strong> or the filters above to reach older entries.</p>
        </x-help-panel>
        <a href="{{ route('activity-logs.export', request()->query()) }}" class="btn btn-secondary btn-sm">Save as PDF</a>
        @if($showAll)
            <a href="{{ route('activity-logs.index', request()->except('all')) }}" class="btn btn-ghost btn-sm">Hide auth events</a>
        @else
            <a href="{{ route('activity-logs.index', array_merge(request()->query(), ['all' => 1])) }}" class="btn btn-ghost btn-sm">Show all (incl. sign-ins)</a>
        @endif
    </div>
</div>

@if(! $isHistory && ! $hasFilters)
    <div class="alert alert-info" style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
        <span>Showing today's activity only. Older entries aren't deleted — view them anytime.</span>
        <a href="{{ route('activity-logs.index', array_merge(request()->query(), ['history' => 1])) }}" class="btn btn-sm btn-secondary">View Full History</a>
    </div>
@elseif($isHistory)
    <div class="alert alert-info" style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
        <span>Showing full history.</span>
        <a href="{{ route('activity-logs.index', collect(request()->query())->except(['history'])->all()) }}" class="btn btn-sm btn-secondary">Back to Today</a>
    </div>
@endif

{{-- Filters --}}
<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('activity-logs.index') }}" class="form-grid" style="align-items:end;">
        @if($showAll)<input type="hidden" name="all" value="1">@endif
        @if($isHistory)<input type="hidden" name="history" value="1">@endif
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="search">Search</label>
            <input type="text" id="search" name="search" value="{{ request('search') }}" class="form-input" placeholder="e.g. Harvest Batch, crop cycle…">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="user">Team member</label>
            <select id="user" name="user" class="form-select">
                <option value="">Everyone</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ (string) request('user') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="action">Action</label>
            <select id="action" name="action" class="form-select">
                <option value="">All actions</option>
                @foreach($actions as $a)
                    <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$a)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="date">Date</label>
            <input type="date" id="date" name="date" value="{{ request('date') }}" class="form-input">
        </div>
        <div class="form-group" style="margin-bottom:0; display:flex; gap:8px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('activity-logs.index', array_filter(['all' => $showAll ? 1 : null, 'history' => $isHistory ? 1 : null])) }}" class="btn btn-ghost">Reset</a>
        </div>
    </form>
</div>

@if($logs->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="icon">📋</div>
            <h3>No activity recorded</h3>
            <p>Business operations performed by your team will appear here.</p>
        </div>
    </div>
@else
    <div class="card" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:130px;">When</th>
                        <th style="width:140px;">Who</th>
                        <th style="width:90px;">Action</th>
                        <th style="width:130px;">Record type</th>
                        <th>What happened</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    @php
                        $color = match($log->action) {
                            'created'                          => 'active',
                            'deleted', 'revoked_approval'      => 'down',
                            'updated'                          => 'sown',
                            // Domain transitions
                            'activated', 'assigned', 'ordered' => 'planned',
                            'completed', 'confirmed',
                            'checked_in', 'received',
                            'delivered', 'approved_email'      => 'completed',
                            'cancelled'                        => 'cancelled',
                            'transplanted'                     => 'transplanted',
                            'acknowledged'                     => 'ready',
                            default                            => 'neutral',
                        };
                    @endphp
                    <tr>
                        <td style="white-space:nowrap; color:var(--text-muted); font-size:12px;" title="{{ $log->created_at->format('M d, Y H:i:s') }}">
                            {{ $log->created_at->format('M d, H:i') }}
                        </td>
                        <td style="font-weight:500;">{{ $log->user->name ?? 'System' }}</td>
                        <td><span class="badge badge-{{ $color }}">{{ ucwords(str_replace('_', ' ', $log->action)) }}</span></td>
                        <td style="color:var(--text-secondary);">{{ $log->subjectLabel() ?? '—' }}</td>
                        <td style="max-width:420px; color:var(--text-secondary); font-size:13.5px;">{{ $log->description }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:16px;">
        {{ $logs->links() }}
    </div>
@endif
@endsection
