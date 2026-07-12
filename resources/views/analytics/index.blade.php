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
    // Renders a compact inline SVG sparkline from an array of values.
    $sparkline = function (array $values, string $stroke = 'var(--olive)') {
        if (count($values) < 2) return '';
        $w = 60; $h = 20; $pad = 2;
        $min = min($values); $max = max($values); $range = ($max - $min) ?: 1;
        $n = count($values);
        $pts = [];
        foreach ($values as $i => $v) {
            $x = $pad + ($i / ($n - 1)) * ($w - 2 * $pad);
            $y = $h - $pad - (($v - $min) / $range) * ($h - 2 * $pad);
            $pts[] = round($x, 1) . ',' . round($y, 1);
        }
        $poly = implode(' ', $pts);
        $last = end($pts);
        [$lx, $ly] = explode(',', $last);
        return '<svg width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '" style="display:block;">'
            . '<polyline points="' . $poly . '" fill="none" stroke="' . $stroke . '" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"/>'
            . '<circle cx="' . $lx . '" cy="' . $ly . '" r="2.2" fill="' . $stroke . '"/></svg>';
    };
    $accentStroke = [
        'success' => 'var(--success)', 'warning' => 'var(--warning)', 'harvest' => 'var(--terracotta)', 'accent' => 'var(--olive)',
    ];
    $cards = [
        ['key' => 'harvest_today',      'label' => 'Harvest Today',     'accent' => 'harvest', 'icon' => 'harvest'],
        ['key' => 'yield_per_acre',     'label' => 'Yield / Acre',      'accent' => 'accent',  'icon' => 'cycles'],
        ['key' => 'revenue',            'label' => 'Revenue (KES)',     'accent' => 'success', 'icon' => 'finance'],
        ['key' => 'cost_per_kg',        'label' => 'Cost / kg (KES)',   'accent' => 'warning', 'icon' => 'inventory'],
        ['key' => 'gross_margin',       'label' => 'Gross Margin (KES)', 'accent' => 'success', 'icon' => 'finance'],
        ['key' => 'orders_pending',     'label' => 'Orders Pending',    'accent' => 'accent',  'icon' => 'sales'],
        ['key' => 'quality_rejection',  'label' => 'Quality Rejection', 'accent' => 'warning', 'icon' => 'quality'],
        ['key' => 'truck_utilisation',  'label' => 'Truck Utilisation', 'accent' => 'accent',  'icon' => 'logistics'],
        ['key' => 'farm_health',        'label' => 'Farm Health',       'accent' => 'success', 'icon' => 'farm'],
        ['key' => 'cash_flow_forecast', 'label' => 'Cash Flow Forecast', 'accent' => 'harvest', 'icon' => 'finance'],
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
        @php $series = $history->get($card['key'], []); @endphp
        <div class="stat-card has-icon">
            <span class="stat-icon {{ $card['accent'] }}"><x-icon :name="$card['icon']" solid /></span>
            <span class="stat-body">
                <span class="stat-label" style="display:block;">{{ $card['label'] }}</span>
                <span class="stat-value {{ $card['accent'] }}">{{ $fmt($snapshots->get($card['key'])) }}</span>
            </span>
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
