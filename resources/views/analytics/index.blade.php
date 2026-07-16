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
    <div class="actions">
        <x-help-panel title="Tips — Executive Dashboard">
            <p>KPIs are <strong>precomputed snapshots</strong>, not live queries — click the refresh icon to recompute them with the latest data.</p>
            <p>The sparkline next to each figure shows its recent trend at a glance.</p>
            <p><strong>Profit &amp; Loss</strong> reads directly from the ledger — post transactions in Finance to see it populate.</p>
            <p><strong>Active Alerts</strong> flags anything outside normal thresholds across modules.</p>
            <p><strong>Quick steps:</strong> Click the refresh icon next to "Generate Report" to recompute → wait for the KPI cards to update.</p>
        </x-help-panel>
        @if(\App\Support\ModuleAccess::allows(auth()->user(), 'ai'))
        <a href="{{ route('ai-reports.create') }}" class="btn btn-secondary">Generate Report</a>
        @endif
        <form action="{{ route('analytics.recompute') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary" title="Recompute snapshots" aria-label="Recompute snapshots" style="padding:9px 11px;">
                <x-icon name="cycles" size="18" />
            </button>
        </form>
    </div>
</div>

{{-- AI Insights — written by AI on a schedule / after each recompute, never on page load. --}}
@if(\App\Support\ModuleAccess::allows(auth()->user(), 'ai') && $narrative?->isCompleted())
<div class="card" style="margin-bottom:16px;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <h3 class="card-title">AI Insights</h3>
        <span class="badge badge-active">Updated {{ $narrative->narrative_date->format('M d') }}</span>
    </div>
    <p style="margin:0; color:var(--text-primary); line-height:1.7;">{{ $narrative->content }}</p>
</div>
@endif

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

{{-- ---- Profit & Loss / ledger summary ---- --}}
<div class="page-header" style="margin-top: 8px;">
    <div>
        <h3 class="card-title" style="font-size: 17px;">Profit &amp; Loss</h3>
        <p class="page-subtitle">From the native ledger @if($pnl['as_of']) · to {{ \Carbon\Carbon::parse($pnl['as_of'])->format('M d, Y') }} @endif</p>
    </div>
    <a href="{{ route('finance.index') }}" class="btn btn-ghost btn-sm">Open Finance</a>
</div>

@if($pnl['posted'] === 0)
    <div class="card" style="margin-bottom: 24px;">
        <div class="alert alert-info" style="margin: 0;">No ledger transactions have been posted yet. Post cost allocations and sales from <a href="{{ route('finance.index') }}">Finance</a> to populate the P&amp;L.</div>
    </div>
@else
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="stat-card has-icon">
            <span class="stat-icon success"><x-icon name="finance" solid /></span>
            <span class="stat-body">
                <span class="stat-label" style="display:block;">Revenue</span>
                <span class="stat-value success">{{ number_format($pnl['revenue']) }}</span>
            </span>
        </div>
        <div class="stat-card has-icon">
            <span class="stat-icon warning"><x-icon name="inventory" solid /></span>
            <span class="stat-body">
                <span class="stat-label" style="display:block;">Expenses</span>
                <span class="stat-value warning">{{ number_format($pnl['expenses']) }}</span>
            </span>
        </div>
        <div class="stat-card has-icon">
            <span class="stat-icon {{ $pnl['net'] >= 0 ? 'success' : 'danger' }}"><x-icon name="cycles" solid /></span>
            <span class="stat-body">
                <span class="stat-label" style="display:block;">Net {{ $pnl['net'] >= 0 ? 'Profit' : 'Loss' }}</span>
                <span class="stat-value" style="color: {{ $pnl['net'] >= 0 ? 'var(--success)' : 'var(--danger)' }};">{{ number_format($pnl['net']) }}</span>
            </span>
        </div>
        <div class="stat-card has-icon">
            <span class="stat-icon accent"><x-icon name="finance" solid /></span>
            <span class="stat-body">
                <span class="stat-label" style="display:block;">Cash &amp; Bank</span>
                <span class="stat-value accent">{{ number_format($pnl['cash']) }}</span>
            </span>
        </div>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header" style="display:flex; justify-content: space-between; align-items:center;">
            <h3 class="card-title">Statement</h3>
            @if($pnl['margin'] !== null)
                <span class="kpi-chip">Net margin: <strong>{{ number_format($pnl['margin'], 1) }}%</strong></span>
            @endif
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Account</th><th>Type</th><th style="text-align:right;">Amount (KES)</th></tr></thead>
                <tbody>
                    @foreach($pnl['revenue_accounts'] as $a)
                    <tr>
                        <td style="font-weight:600; color: var(--text-primary);">{{ $a['name'] }} <span style="font-family: var(--font-mono); font-size:11px; color: var(--text-muted);">{{ $a['code'] }}</span></td>
                        <td><span class="badge badge-active">Income</span></td>
                        <td style="text-align:right;">{{ number_format($a['balance']) }}</td>
                    </tr>
                    @endforeach
                    @foreach($pnl['expense_accounts'] as $a)
                    <tr>
                        <td style="font-weight:600; color: var(--text-primary);">{{ $a['name'] }} <span style="font-family: var(--font-mono); font-size:11px; color: var(--text-muted);">{{ $a['code'] }}</span></td>
                        <td><span class="badge badge-maintenance">Expense</span></td>
                        <td style="text-align:right;">({{ number_format($a['balance']) }})</td>
                    </tr>
                    @endforeach
                    <tr style="border-top: 2px solid var(--border-strong);">
                        <td style="font-weight:700; color: var(--text-primary);">Net {{ $pnl['net'] >= 0 ? 'Profit' : 'Loss' }}</td>
                        <td></td>
                        <td style="text-align:right; font-weight:700; color: {{ $pnl['net'] >= 0 ? 'var(--success)' : 'var(--danger)' }};">{{ number_format($pnl['net']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
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
