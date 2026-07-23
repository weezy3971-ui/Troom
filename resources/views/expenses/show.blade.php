@extends('layouts.app')
@section('title', 'Expense')

@section('content')
<x-crumb-nav />
@php $canDisburse = \App\Support\ModuleAccess::allows(auth()->user(), 'finance'); @endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ ucfirst(str_replace('_', ' ', $expense->category)) }}</h1>
        <p class="page-subtitle">{{ $expense->expense_date->format('M d, Y') }} · KES {{ number_format($expense->amount, 2) }}</p>
    </div>
    <div class="actions">
        @if($canDisburse && $expense->vendor?->isPayable() && ! $expense->isDisbursed())
            <form action="{{ route('expenses.disburse', $expense) }}" method="POST"
                  data-confirm="Send KES {{ number_format((float) $expense->amount, 2) }} to {{ $expense->vendor->name }} via M-Pesa? This cannot be recalled.">
                @csrf
                <button type="submit" class="btn btn-primary">Disburse via M-Pesa (B2C)</button>
            </form>
        @endif
        @if($expense->isVouchered())
            <a href="{{ route('expenses.voucher', $expense) }}" class="btn btn-secondary">View Voucher</a>
        @elseif($expense->vendor_id)
            <form action="{{ route('expenses.issue-voucher', $expense) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-secondary">Issue Payment Voucher</button>
            </form>
        @endif
        @if($expense->isLocked())
            <span class="badge badge-neutral" title="Expenses can only be edited or deleted within a day of being logged">🔒 Locked</span>
        @else
            <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-secondary">Edit</a>
            <form action="{{ route('expenses.destroy', $expense) }}" method="POST" data-confirm="Delete this expense? This is only possible because it was logged less than a day ago — it will be permanently removed.">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        @endif
    </div>
</div>

@if(! $expense->vendor_id && ! $expense->isLocked())
    <div class="alert alert-info" style="margin-bottom:20px;">
        No vendor is set on this expense, so no payment voucher can be issued. Edit the expense to add one.
    </div>
@endif

@if($expense->isLocked())
    <div class="alert alert-info" style="margin-bottom:20px;">
        This expense was logged more than a day ago and is now locked — it can no longer be edited or deleted, only viewed.
    </div>
@endif

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
        <div class="detail-label">Mode of Payment</div>
        <div class="detail-value">{{ $expense->payment_mode ? ucfirst(str_replace('_', ' ', $expense->payment_mode)) : '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Paid To</div>
        <div class="detail-value">
            @if($expense->vendor)
                <a href="{{ route('vendors.edit', $expense->vendor) }}">{{ $expense->vendor->name }}</a>
            @else
                —
            @endif
        </div>
    </div>
    @if($expense->isVouchered())
    <div class="detail-item">
        <div class="detail-label">Voucher</div>
        <div class="detail-value mono">{{ $expense->voucher_number }}</div>
    </div>
    @endif
    @if($expense->isDisbursed())
    <div class="detail-item">
        <div class="detail-label">M-Pesa Receipt</div>
        <div class="detail-value mono">{{ $expense->mpesaTransactions->firstWhere('status', 'success')->mpesa_receipt_number }}</div>
    </div>
    @endif
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

@if($expense->receipt_path)
<div class="card" style="margin-top: 24px;">
    <div class="card-header"><h3 class="card-title">Receipt</h3></div>
    <a href="{{ $expense->receiptUrl() }}" target="_blank" rel="noopener">
        <img src="{{ $expense->receiptUrl() }}" alt="Receipt" style="max-width: 320px; border-radius: 8px; border: 1px solid var(--border); display: block;">
    </a>
</div>
@endif
@endsection
