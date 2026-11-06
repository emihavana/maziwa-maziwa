@php
    $dayCount = $dates->count();
    $fontSize = $dayCount > 20 ? 6 : ($dayCount > 12 ? 7 : 9);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Farmer Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: {{ $fontSize }}px; color: #1f2937; }
        h3 { margin: 0 0 2px; font-size: 13px; }
        .meta { margin: 0 0 8px; color: #6b7280; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 3px; text-align: right; }
        th { background: #f3f4f6; }
        th.left, td.left { text-align: left; }
        tr.totals td { background: #f3f4f6; font-weight: bold; }
    </style>
</head>
<body>
    <h3>Farmer Report: {{ $from }} to {{ $to }}</h3>
    <div class="meta">
        Routes: {{ $routeLabel }} &nbsp;|&nbsp; Litres per day. Debt and net pay in KES.
        &nbsp;|&nbsp; Generated {{ now()->format('d M Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="left">Farmer</th>
                <th class="left">Phone</th>
                <th class="left">Route</th>
                @foreach ($dates as $d)
                    <th>{{ \Carbon\Carbon::parse($d)->format('d/m') }}</th>
                @endforeach
                <th>Total L</th>
                <th>Debt</th>
                <th>Net pay</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($farmers as $f)
                <tr>
                    <td class="left">{{ $f->name }}</td>
                    <td class="left">{{ $f->phone }}</td>
                    <td class="left">{{ $f->route?->name }}</td>
                    @foreach ($dates as $d)
                        <td>{{ isset($f->daily[$d]) ? number_format((float) $f->daily[$d], 1) : '-' }}</td>
                    @endforeach
                    <td>{{ number_format($f->total_qty, 2) }}</td>
                    <td>{{ number_format($f->debt, 2) }}</td>
                    <td>{{ number_format($f->net, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="left" colspan="{{ $dayCount + 6 }}">No farmers found for the selected routes.</td>
                </tr>
            @endforelse

            @if ($farmers->isNotEmpty())
                <tr class="totals">
                    <td class="left" colspan="3">Total ({{ $farmers->count() }} farmers)</td>
                    @foreach ($dates as $d)
                        <td>{{ number_format((float) $farmers->sum(fn ($f) => $f->daily[$d] ?? 0), 1) }}</td>
                    @endforeach
                    <td>{{ number_format($farmers->sum('total_qty'), 2) }}</td>
                    <td>{{ number_format($farmers->sum('debt'), 2) }}</td>
                    <td>{{ number_format($farmers->sum('net'), 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>