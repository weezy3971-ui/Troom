@extends('layouts.app')
@section('title', 'Expense')

@section('content')
<x-crumb-nav />

<div class="page-header">
    <div>
        <h1 class="page-title">{{ ucfirst(str_replace('_', ' ', $expense->category)) }}</h1>
        <p class="page-subtitle">{{ $expense->expense_date->format('M d, Y') }} · KES {{ number_format($expense->amount, 2) }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('expenses.destroy', $expense) }}" method="POST" data-confirm="Delete this expense?">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Category</div>
        <div class="detail-value"><span class="badge badge-neutral">{{ ucfirst(str_replace('_', ' ', $expense->category)) }}</span></div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Amount</div>
        <div class="detail-value">KES {{ number_format($expense->amount, 2) }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Farm</div>
        <div class="detail-value">{{ $expense->farm->name ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Block</div>
        <div class="detail-value">{{ $expense->block->name ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Logged By</div>
        <div class="detail-value">{{ $expense->logger->name ?? '—' }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Description</h3></div>
    <p style="font-size: 13px; color: var(--text-secondary);">{{ $expense->description }}</p>
</div>
@endsection
