<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Activity Log — {{ $generatedAt->format('Y-m-d') }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #17231B; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .meta { color: #5C6A61; font-size: 12px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 5px 8px; border-bottom: 1px solid #DCE2D3; vertical-align: top; }
        th { background: #E8EDE2; font-size: 10px; text-transform: uppercase; letter-spacing: 0.03em; }
        tr:nth-child(even) td { background: #F7F9F4; }
        .col-when { width: 110px; white-space: nowrap; }
        .col-who { width: 130px; }
        .col-action { width: 100px; }
        .col-type { width: 130px; }

        /* Toolbar is only for the screen — never on the printed sheet. */
        .toolbar { margin-bottom: 18px; display: flex; gap: 10px; }
        .btn { font: inherit; font-size: 13px; padding: 8px 14px; border-radius: 6px;
               border: 1px solid #C4CDBA; background: #fff; color: #17231B;
               cursor: pointer; text-decoration: none; }
        .btn-primary { background: #2F6B3E; border-color: #2F6B3E; color: #fff; }

        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            @page { size: A4 landscape; margin: 12mm; }
            thead { display: table-header-group; } /* repeat header on each page */
            tr { break-inside: avoid; }
            th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()">⬇ Save as PDF / Print</button>
        <a href="{{ url()->previous() }}" class="btn">← Back to Activity Log</a>
    </div>

    <h1>Trooms House — Activity Log</h1>
    <div class="meta">{{ $scopeLabel }} · Generated {{ $generatedAt->format('M d, Y H:i') }} · {{ $logs->count() }} entries</div>

    <table>
        <thead>
            <tr>
                <th class="col-when">When</th>
                <th class="col-who">Who</th>
                <th class="col-action">Action</th>
                <th class="col-type">Record type</th>
                <th>What happened</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
                <tr>
                    <td class="col-when">{{ $log->created_at->format('M d, H:i') }}</td>
                    <td class="col-who">{{ $log->user->name ?? 'System' }}</td>
                    <td class="col-action">{{ ucwords(str_replace('_', ' ', $log->action)) }}</td>
                    <td class="col-type">{{ $log->subjectLabel() ?? '—' }}</td>
                    <td>{{ $log->description }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <script>
        // Open the print dialog automatically, like the planner's Save-as-PDF.
        window.addEventListener('load', function () { window.print(); });
    </script>
</body>
</html>
