{{--
    Payment voucher — Trooms's own proof that a vendor was paid. The outbound
    mirror of payments/receipt.blade.php: same document shape, opposite
    direction of money, so a printed voucher and a printed customer receipt
    read as the same family of document.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Voucher {{ $expense->voucher_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; color: #17231B; background: #f3f4f1; padding: 24px; }
        .receipt { max-width: 460px; margin: 0 auto; background: #fff; border: 1px solid #DCE2D3; border-radius: 10px; padding: 28px 30px; }
        .brand { display: flex; align-items: center; gap: 10px; border-bottom: 2px solid #B0562F; padding-bottom: 14px; margin-bottom: 18px; }
        .brand .logo { width: 34px; height: 34px; background: #B0562F; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .brand h1 { font-size: 18px; letter-spacing: -0.2px; }
        .brand .sub { font-size: 11px; color: #5C6A61; }
        .rc-no { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 18px; }
        .rc-no .label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.6px; color: #5C6A61; }
        .rc-no .val { font-family: 'Courier New', monospace; font-size: 15px; font-weight: 700; }
        .rows { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .rows td { padding: 7px 0; font-size: 13.5px; border-bottom: 1px dashed #E4E9DD; }
        .rows td:first-child { color: #5C6A61; }
        .rows td:last-child { text-align: right; font-weight: 600; }
        .total { display: flex; justify-content: space-between; align-items: center; background: #FBEFE9; border-radius: 8px; padding: 12px 14px; margin-bottom: 8px; }
        .total .lbl { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #43554A; }
        .total .amt { font-size: 20px; font-weight: 800; color: #B0562F; }
        .foot { text-align: center; font-size: 11px; color: #5C6A61; border-top: 1px solid #ECEEED; padding-top: 14px; }
        .actions { max-width: 460px; margin: 18px auto 0; text-align: center; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .actions button, .actions a { font: inherit; font-size: 13px; padding: 9px 18px; border-radius: 8px; border: 1px solid #C4CEB7; background: #fff; color: #17231B; cursor: pointer; text-decoration: none; }
        .actions .primary { background: #B0562F; color: #fff; border-color: #B0562F; }
        @media print { body { background: #fff; padding: 0; } .receipt { border: none; } .actions { display: none; } }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="brand">
            <div class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                     fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1.9 10.3 12 1.3l10.1 9"/>
                    <path d="M4.5 10.7v8.8a2.4 2.4 0 0 0 2.4 2.4h10.2a2.4 2.4 0 0 0 2.4-2.4v-8.8"/>
                    <path d="M10.1 21.9V13a1.45 1.45 0 0 1 2.9 0v8.9"/>
                </svg>
            </div>
            <div>
                <h1>Trooms House</h1>
                <div class="sub">Farms &amp; Equestrian — Payment Voucher</div>
            </div>
        </div>

        <div class="rc-no">
            <div>
                <div class="label">Voucher No.</div>
                <div class="val">{{ $expense->voucher_number }}</div>
            </div>
            <div style="text-align: right;">
                <div class="label">Date</div>
                <div class="val">{{ $expense->expense_date->format('d M Y') }}</div>
            </div>
        </div>

        <table class="rows">
            <tr><td>Paid to</td><td>{{ $expense->vendor->name }}</td></tr>
            @if($expense->vendor->phone)
                <tr><td>Phone</td><td>{{ $expense->vendor->phone }}</td></tr>
            @endif
            <tr><td>Category</td><td>{{ ucfirst(str_replace('_', ' ', $expense->category)) }}</td></tr>
            <tr><td>Payment mode</td><td>{{ $expense->payment_mode ? ucfirst(str_replace('_', ' ', $expense->payment_mode)) : '—' }}</td></tr>
            @if($expense->farm)
                <tr><td>Farm</td><td>{{ $expense->farm->name }}@if($expense->block) — {{ $expense->block->name }}@endif</td></tr>
            @endif
            @if($expense->logger)
                <tr><td>Authorised by</td><td>{{ $expense->logger->name }}</td></tr>
            @endif
        </table>

        <div class="total">
            <span class="lbl">Amount paid</span>
            <span class="amt">KES {{ number_format((float) $expense->amount, 2) }}</span>
        </div>

        <p style="font-size: 12.5px; color: var(--text-secondary, #43554A); margin: 12px 0 18px;">
            For: {{ $expense->description }}
        </p>

        <div class="foot">
            Trooms House Farms &amp; Equestrian · This voucher is Trooms's record of payment made,<br>
            not a receipt issued by {{ $expense->vendor->name }}.
        </div>
    </div>

    <div class="actions">
        <button class="primary" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ route('expenses.show', $expense) }}">Back to expense</a>
    </div>
</body>
</html>
