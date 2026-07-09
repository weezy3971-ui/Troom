@extends('layouts.app')
@section('title', 'Executive Dashboard')

@php
    $fmt = function ($snap) {
        if (! $snap) return '—';
        $v = (float) $snap->value;
        return match ($snap->unit) {
            'KES', 'KES/kg' => number_format($v, 2),
            '%' => number_format($v, 1) . '%',
            'kg', 'kg/acre' => number_format($v, 1),
            default => number_format($v, 0),
        };
    };
    $cards = [
        ['key' => 'harvest_today',      'label' => 'Harvest Today',     'accent' => 'harvest'],
        ['key' => 'yield_per_acre',     'label' => 'Yield / Acre',      'accent' => 'accent'],
        ['key' => 'revenue',            'label' => 'Revenue (KES)',     'accent' => 'success'],
        ['key' => 'cost_per_kg',        'label' => 'Cost / kg (KES)',   'accent' => 'warning'],
        ['key' => 'gross_margin',       'label' => 'Gross Margin (KES)', 'accent' => 'success'],
        ['key' => 'orders_pending',     'label' => 'Orders Pending',    'accent' => 'accent'],
        ['key' => 'quality_rejection',  'label' => 'Quality Rejection', 'accent' => 'warning'],
        ['key' => 'truck_utilisation',  'label' => 'Truck Utilisation', 'accent' => 'accent'],
        ['key' => 'farm_health',        'label' => 'Farm Health',       'accent' => 'success'],
        ['key' => 'cash_flow_forecast', 'label' => 'Cash Flow Forecast', 'accent' => 'harvest'],
    ];
@endphp

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Executive Dashboard</h1>
        <p class="page-subtitle">
            Precomputed KPI snapshots
            @if($latestDate) · as of {{ \Carbon\Carbon::parse($latestDate)->format('M d, Y') }} @else · not yet computed @endif
        </p>
    </div>
    <form action="{{ route('analytics.recompute') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-primary">Recompute Snapshots</button>
    </form>
</div>

@if($snapshots->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="icon">📊</div>
            <h3>No snapshots computed yet</h3>
            <p>The dashboard reads only from precomputed snapshots. Run a computation to populate it.</p>
            <form action="{{ route('analytics.recompute') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">Recompute Snapshots</button>
            </form>
        </div>
    </div>
@else
    <div class="stats-grid">
        @foreach($cards as $card)
        <div class="stat-card">
            <div class="stat-label">{{ $card['label'] }}</div>
            <div class="stat-value {{ $card['accent'] }}">{{ $fmt($snapshots->get($card['key'])) }}</div>
        </div>
        @endforeach
    </div>
@endif

<div class="card">
    <div class="card-header"><h3 class="card-title">Active Alerts</h3></div>
    @if(empty($alerts))
        <p class="page-subtitle">No active alerts. All modules within thresholds.</p>
    @else
        @foreach($alerts as $alert)
            <div class="alert alert-{{ $alert['severity'] === 'danger' ? 'error' : $alert['severity'] }}" style="animation: none;">
                <strong style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.6px;">{{ str_replace('_', ' ', $alert['type']) }}</strong>
                <span>{{ $alert['message'] }}</span>
            </div>
        @endforeach
    @endif
</div>
@endsection
