{{--
    The customer-facing receipt. Standalone rather than inside layouts.app:
    it is printed and saved as PDF from the browser, so it carries its own
    styles and no chrome. Colours are the project palette written literally,
    since print has no access to the app's CSS variables.

    Modelled on rides/receipt.blade.php — same document, different payable.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $payment->receipt_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; color: #17231B; background: #f3f4f1; padding: 24px; }
        .receipt { max-width: 460px; margin: 0 auto; background: #fff; border: 1px solid #DCE2D3; border-radius: 10px; padding: 28px 30px; position: relative; overflow: hidden; }
        .brand { display: flex; align-items: center; gap: 10px; border-bottom: 2px solid #2F6B3B; padding-bottom: 14px; margin-bottom: 18px; }
        .brand .logo { width: 34px; height: 34px; background: #2F6B3B; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .brand h1 { font-size: 18px; letter-spacing: -0.2px; }
        .brand .sub { font-size: 11px; color: #5C6A61; }
        .rc-no { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 18px; }
        .rc-no .label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.6px; color: #5C6A61; }
        .rc-no .val { font-family: 'Courier New', monospace; font-size: 15px; font-weight: 700; }
        .rows { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .rows td { padding: 7px 0; font-size: 13.5px; border-bottom: 1px dashed #E4E9DD; }
        .rows td:first-child { color: #5C6A61; }
        .rows td:last-child { text-align: right; font-weight: 600; }
        .total { display: flex; justify-content: space-between; align-items: center; background: #F0F5EE; border-radius: 8px; padding: 12px 14px; margin-bottom: 8px; }
        .total .lbl { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #43554A; }
        .total .amt { font-size: 20px; font-weight: 800; color: #2F6B3B; }
        .balance { text-align: right; font-size: 12px; color: #5C6A61; margin-bottom: 20px; }
        .balance strong { color: #17231B; }
        .balance .settled { color: #1F5127; font-weight: 700; }
        .foot { text-align: center; font-size: 11px; color: #5C6A61; border-top: 1px solid #ECEEED; padding-top: 14px; }
        /* A voided receipt must be unusable at a glance, on paper as well as
           on screen — hence a watermark rather than only a status line. */
        .void-stamp {
            position: absolute; top: 46%; left: 50%;
            transform: translate(-50%, -50%) rotate(-18deg);
            font-size: 58px; font-weight: 800; letter-spacing: 6px;
            color: rgba(124, 36, 24, 0.16); border: 6px solid rgba(124, 36, 24, 0.16);
            padding: 4px 22px; border-radius: 10px; pointer-events: none;
        }
        .void-note { background: #FBEAE7; color: #7C2418; border-radius: 8px; padding: 10px 12px; font-size: 12px; margin-bottom: 16px; }
        .actions { max-width: 460px; margin: 18px auto 0; text-align: center; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .actions button, .actions a { font: inherit; font-size: 13px; padding: 9px 18px; border-radius: 8px; border: 1px solid #C4CEB7; background: #fff; color: #17231B; cursor: pointer; text-decoration: none; }
        .actions .primary { background: #2F6B3B; color: #fff; border-color: #2F6B3B; }
        @media print { body { background: #fff; padding: 0; } .receipt { border: none; } .actions { display: none; } }
    </style>
</head>
<body>
    <div class="receipt">
        @if($payment->isVoided())
            <div class="void-stamp">VOID</div>
        @endif

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
                <div class="sub">Farms &amp; Equestrian — Payment Receipt</div>
            </div>
        </div>

        @if($payment->isVoided())
            <div class="void-note">
                <strong>This receipt has been voided</strong>
                @if($payment->void_reason) — {{ $payment->void_reason }} @endif
                <br>Voided {{ $payment->voided_at->format('M d, Y H:i') }}
                @if($payment->voidedBy) by {{ $payment->voidedBy->name }} @endif.
                It is no longer proof of payment.
            </div>
        @endif

        <div class="rc-no">
            <div>
                <div class="label">Receipt No.</div>
                <div class="val">{{ $payment->receipt_number }}</div>
            </div>
            <div style="text-align: right;">
                <div class="label">Date</div>
                <div class="val">{{ $payment->paid_at->format('d M Y') }}</div>
            </div>
        </div>

        <table class="rows">
            <tr><td>Received from</td><td>{{ $payment->customer?->name ?? 'Walk-in customer' }}</td></tr>
            @if($payment->payer_phone)
                <tr><td>Phone</td><td>{{ $payment->payer_phone }}</td></tr>
            @endif
            @if($order?->invoice_number)
                <tr><td>Invoice</td><td>{{ $order->invoice_number }}</td></tr>
            @endif
            @if($order?->crop)
                <tr><td>Produce</td><td>{{ $order->crop->name }}</td></tr>
            @endif
            <tr><td>Payment method</td><td>{{ $payment->methodLabel() }}</td></tr>
            @if($payment->reference)
                <tr><td>{{ $payment->method === 'mpesa' ? 'M-Pesa code' : 'Reference' }}</td><td>{{ $payment->reference }}</td></tr>
            @endif
            @if($payment->receivedBy)
                <tr><td>Received by</td><td>{{ $payment->receivedBy->name }}</td></tr>
            @endif
        </table>

        <div class="total">
            <span class="lbl">Amount received</span>
            <span class="amt">KES {{ number_format((float) $payment->amount, 2) }}</span>
        </div>

        @if($order && ! $payment->isVoided())
            <div class="balance">
                @if($order->balanceDue() > 0)
                    Balance still due on {{ $order->invoice_number ?? 'this order' }}:
                    <strong>KES {{ number_format($order->balanceDue(), 2) }}</strong>
                @else
                    <span class="settled">Paid in full — nothing further due.</span>
                @endif
            </div>
        @endif

        <div class="foot">
            Thank you for your business.<br>
            Trooms House Farms &amp; Equestrian · Keep this receipt as proof of payment.
        </div>
    </div>

    <div class="actions">
        <button class="primary" onclick="window.print()">Print / Save as PDF</button>
        @if($order)
            <a href="{{ route('sales-orders.show', $order) }}">Back to order</a>
        @endif
        <a href="{{ route('payments.index') }}">All payments</a>
    </div>
</body>
</html>
