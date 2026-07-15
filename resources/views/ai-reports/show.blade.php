@extends('layouts.app')
@section('title', $report->title)

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('ai-reports.index') }}">AI Reports</a> <span>/</span> <span>{{ $report->title }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $report->title }}</h1>
        <p class="page-subtitle">
            {{ $report->typeLabel() }}
            @if($report->model) · {{ $report->model }} @endif
            @if($report->isCompleted()) · {{ $report->input_tokens + $report->output_tokens }} tokens @endif
        </p>
    </div>
    <div class="actions">
        <form action="{{ route('ai-reports.regenerate', $report) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-secondary btn-sm">Regenerate</button>
        </form>
        <form action="{{ route('ai-reports.destroy', $report) }}" method="POST" onsubmit="return confirm('Delete this report?');">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-ghost btn-sm">Delete</button>
        </form>
    </div>
</div>

@if($report->isFailed())
    <div class="card">
        <div class="alert alert-danger" style="margin:0;">
            <strong>Generation failed.</strong> {{ $report->error }}
        </div>
    </div>
@elseif($report->isCompleted())
    <div class="card">
        <div class="prose">{!! \Illuminate\Support\Str::markdown($report->content ?? '') !!}</div>
    </div>
    <p class="page-subtitle" style="margin-top:12px;">
        Generated {{ $report->created_at->format('M d, Y H:i') }}
        @if($report->generatedBy) by {{ $report->generatedBy->name }} @endif ·
        Figures are drawn from your KPI snapshots — the AI narrates them, it does not source them.
    </p>
@else
    <div class="card">
        <div class="empty-state"><div class="icon">⏳</div><h3>Generating…</h3><p>This report is still being written.</p></div>
    </div>
@endif
@endsection
