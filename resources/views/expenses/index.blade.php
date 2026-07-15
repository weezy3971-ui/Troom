@extends('layouts.app')
@section('title', 'Expenses')

@php
    $categoryLabels = collect($categories)->mapWithKeys(fn($c) => [$c => ucfirst(str_replace('_', ' ', $c))]);
@endphp

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Expenses</h1>
        <p class="page-subtitle">Field spend — tools, fuel, fines, casual labour, and everything else</p>
    </div>
    <a href="{{ route('expenses.create') }}" class="btn btn-primary">+ Log Expense</a>
</div>

<div class="stat-card" style="max-width: 280px; margin-bottom: 20px;">
    <div class="stat-label">Total (filtered)</div>
    <div class="stat-value">KES {{ number_format($total, 2) }}</div>
</div>

<x-search-bar
    :action="route('expenses.index')"
    placeholder="Search by category, description, farm, or who logged it…"
    :search="$search"
    :total="$expenses->count()"
    :filters="[['name' => 'category', 'label' => 'All categories', 'options' => $categoryLabels]]"
/>

@if($expenses->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search || $category)
                <div class="icon">🔍</div>
                <h3>No expenses match your filters</h3>
                <p>Try a different search term or clear your filters.</p>
                <a href="{{ route('expenses.index') }}" class="btn btn-ghost">Clear Filters</a>
            @else
                <div class="icon">💸</div>
                <h3>No expenses logged yet</h3>
                <p>Log field spend as it happens — tools, fuel, fines, anything.</p>
                <a href="{{ route('expenses.create') }}" class="btn btn-primary">+ Log Expense</a>
            @endif
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Farm</th>
                        <th>Amount</th>
                        <th>Logged By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenses as $expense)
                    <tr>
                        <td>{{ $expense->expense_date->format('M d, Y') }}</td>
                        <td><span class="badge badge-neutral">{{ $categoryLabels[$expense->category] ?? ucfirst($expense->category) }}</span></td>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('expenses.show', $expense) }}" style="color: var(--accent-hover); text-decoration: none;">{{ \Illuminate\Support\Str::limit($expense->description, 60) }}</a>
                        </td>
                        <td>{{ $expense->farm->name ?? '—' }}</td>
                        <td class="mono">{{ number_format($expense->amount, 2) }}</td>
                        <td>{{ $expense->logger->name ?? '—' }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('expenses.show', $expense) }}" class="btn btn-ghost btn-sm">View</a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
