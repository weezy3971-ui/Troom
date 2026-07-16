<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Activity Log</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #17231B; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .meta { color: #5C6A61; font-size: 10px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 5px 8px; border-bottom: 1px solid #DCE2D3; vertical-align: top; }
        th { background: #E8EDE2; font-size: 9px; text-transform: uppercase; letter-spacing: 0.03em; }
        tr:nth-child(even) td { background: #F7F9F4; }
        .col-when { width: 110px; white-space: nowrap; }
        .col-who { width: 110px; }
        .col-action { width: 90px; }
        .col-type { width: 110px; }
    </style>
</head>
<body>
    <h1>Trooms ERP — Activity Log</h1>
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
</body>
</html>
