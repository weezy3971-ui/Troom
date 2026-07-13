<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $ride->receipt_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; color: #17231B; background: #f3f4f1; padding: 24px; }
        .receipt { max-width: 420px; margin: 0 auto; background: #fff; border: 1px solid #DCE2D3; border-radius: 10px; padding: 28px 30px; }
        .brand { display: flex; align-items: center; gap: 10px; border-bottom: 2px solid #2F6B3B; padding-bottom: 14px; margin-bottom: 18px; }
        .brand .logo { width: 34px; height: 34px; background: #2F6B3B; color: #fff; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; }
        .brand h1 { font-size: 18px; letter-spacing: -0.2px; }
        .brand .sub { font-size: 11px; color: #5C6A61; }
        .rc-no { text-align: right; margin-bottom: 18px; }
        .rc-no .label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.6px; color: #5C6A61; }
        .rc-no .val { font-family: 'Courier New', monospace; font-size: 15px; font-weight: 700; }
        .rows { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .rows td { padding: 7px 0; font-size: 13.5px; border-bottom: 1px dashed #E4E9DD; }
        .rows td:first-child { color: #5C6A61; }
        .rows td:last-child { text-align: right; font-weight: 600; }
        .total { display: flex; justify-content: space-between; align-items: center; background: #F0F5EE; border-radius: 8px; padding: 12px 14px; margin-bottom: 8px; }
        .total .lbl { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #43554A; }
        .total .amt { font-size: 20px; font-weight: 800; color: #2F6B3B; }
        .pay { text-align: right; font-size: 12px; color: #5C6A61; margin-bottom: 20px; }
        .pay .paid { color: #1F5127; font-weight: 700; }
        .pay .unpaid { color: #7C2418; font-weight: 700; }
        .foot { text-align: center; font-size: 11px; color: #5C6A61; border-top: 1px solid #ECEEED; padding-top: 14px; }
        .actions { max-width: 420px; margin: 18px auto 0; text-align: center; }
        .actions button, .actions a { font: inherit; font-size: 13px; padding: 9px 18px; border-radius: 8px; border: 1px solid #C4CEB7; background: #fff; color: #17231B; cursor: pointer; text-decoration: none; }
        .actions .primary { background: #2F6B3B; color: #fff; border-color: #2F6B3B; }
        @media print { body { background: #fff; padding: 0; } .receipt { border: none; } .actions { display: none; } }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="brand">
            <div class="logo">T</div>
            <div>
                <h1>Trooms Stables</h1>
                <div class="sub">Horse Riding Receipt</div>
            </div>
        </div>

        <div class="rc-no">
            <div class="label">Receipt No.</div>
            <div class="val">{{ $ride->receipt_number }}</div>
        </div>

        <table class="rows">
            <tr><td>Customer</td><td>{{ $ride->customer_name }}</td></tr>
            @if($ride->customer_phone)<tr><td>Phone</td><td>{{ $ride->customer_phone }}</td></tr>@endif
            <tr><td>Date &amp; Time</td><td>{{ $ride->start_time->format('M d, Y H:i') }}</td></tr>
            <tr><td>Duration</td><td>{{ $ride->duration_minutes }} min</td></tr>
            <tr><td>Ends</td><td>{{ $ride->end_time->format('H:i') }}</td></tr>
            <tr><td>Horse</td><td>{{ $ride->horse?->name ?? 'To be assigned' }}</td></tr>
            <tr><td>Guide</td><td>{{ $ride->guide?->name ?? 'To be assigned' }}</td></tr>
        </table>

        <div class="total">
            <span class="lbl">Total</span>
            <span class="amt">KES {{ number_format($ride->amount) }}</span>
        </div>
        <div class="pay">
            Payment:
            <span class="{{ $ride->payment_status === 'paid' ? 'paid' : 'unpaid' }}">{{ strtoupper($ride->payment_status) }}</span>
        </div>

        <div class="foot">
            Please present this receipt to the stable manager.<br>
            Thank you for riding with Trooms Stables.
        </div>
    </div>

    <div class="actions">
        <button class="primary" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ route('rides.show', $ride) }}">Back to ride</a>
    </div>
</body>
</html>
