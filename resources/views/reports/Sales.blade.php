<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h3 { margin: 0 0 2px; font-size: 14px; }
        .meta { margin: 0 0 10px; color: #6b7280; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 5px; text-align: right; }
        th { background: #f3f4f6; }
        th.left, td.left { text-align: left; }
        tr.totals td { background: #f3f4f6; font-weight: bold; }
        .neg { color: #b91c1c; }
    </style>
</head>
<body>
    <h3>Sales Report: {{ $from }} to {{ $to }}</h3>
    <div class="meta">
        Quantities in litres, amounts in KES. Net revenue = sales revenue minus farmer earnings.
        &nbsp;|&nbsp; Generated {{ now()->format('d M Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="left">Date</th>
                <th>Sales qty (L)</th>
                <th>Sale price / L</th>
                <th>Sales revenue</th>
                <th>Farmer earnings</th>
                <th>Net revenue</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $r)
                <tr>
                    <td class="left">{{ $r['date']->format('d M Y') }}</td>
                    <td>{{ $r['qty'] > 0 ? number_format($r['qty'], 2) : '-' }}</td>
                    <td>{{ $r['qty'] > 0 ? number_format($r['price'], 2) : '-' }}</td>
                    <td>{{ $r['revenue'] > 0 ? number_format($r['revenue'], 2) : '-' }}</td>
                    <td>{{ $r['earnings'] > 0 ? number_format($r['earnings'], 2) : '-' }}</td>
                    <td class="{{ $r['net'] < 0 ? 'neg' : '' }}">
                        {{ ($r['revenue'] > 0 || $r['earnings'] > 0) ? number_format($r['net'], 2) : '-' }}
                    </td>
                </tr>
            @endforeach

            <tr class="totals">
                <td class="left">Total</td>
                <td>{{ number_format($totals['qty'], 2) }}</td>
                <td>{{ number_format($totals['price'], 2) }}</td>
                <td>{{ number_format($totals['revenue'], 2) }}</td>
                <td>{{ number_format($totals['earnings'], 2) }}</td>
                <td class="{{ $totals['net'] < 0 ? 'neg' : '' }}">{{ number_format($totals['net'], 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>