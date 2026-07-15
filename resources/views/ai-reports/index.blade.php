@extends('layouts.app')
@section('title', 'AI Reports')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">AI Reports</h1>
        <p class="page-subtitle">Narrative reports written by AI from your live farm data</p>
    </div>
    <a href="{{ route('ai-reports.create') }}" class="btn btn-primary">+ Generate Report</a>
</div>

@unless($configured)
    <div class="alert alert-warning" style="margin-bottom:16px;">
        AI is not yet configured. Set <code>ANTHROPIC_API_KEY</code> in your <code>.env</code> to enable report generation.
    </div>
@endunless

@if($reports->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="icon">🤖</div>
            <h3>No reports yet</h3>
            <p>Generate an AI executive summary from your latest KPIs, trends, and alerts.</p>
            <a href="{{ route('ai-reports.create') }}" class="btn btn-primary">+ Generate Report</a>
        </div>
    </div>
@else
    <div class="card" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Report</th><th>Period</th><th>Status</th><th>Generated</th><th>By</th></tr>
                </thead>
                <tbody>
                    @foreach($reports as $report)
                    <tr>
                        <td style="font-weight:600; color:var(--text-primary);">
                            <a href="{{ route('ai-reports.show', $report) }}">{{ $report->title }}</a>
                        </td>
                        <td>
                            @if($report->period_start || $report->period_end)
                                {{ $report->period_start?->format('M d') ?? '—' }} → {{ $report->period_end?->format('M d') ?? 'now' }}
                            @else
                                To date
                            @endif
                        </td>
                        <td>
                            @if($report->isCompleted())
                                <span class="badge badge-completed">Completed</span>
                            @elseif($report->isFailed())
                                <span class="badge badge-cancelled">Failed</span>
                            @else
                                <span class="badge badge-active">Pending</span>
                            @endif
                        </td>
                        <td>{{ $report->created_at->format('M d, H:i') }}</td>
                        <td>{{ $report->generatedBy?->name ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
