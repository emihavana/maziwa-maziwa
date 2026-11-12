<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>M-Pesa Payout Report</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #111; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .header h2 { margin: 0 0 5px 0; color: #1a202c; }
        .header p { margin: 0; color: #555; font-size: 13px; }
        .meta-table { width: 100%; margin-bottom: 15px; font-size: 11px; }
        .meta-table td { padding: 3px 0; }
        .summary-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; margin-bottom: 15px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background-color: #f1f5f9; font-weight: bold; color: #334155; }
        .success { color: #16a34a; font-weight: bold; }
        .failed { color: #dc2626; font-weight: bold; }
        .pending { color: #d97706; font-weight: bold; }
        .text-right { text-align: right; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Dairy Management System</h2>
        <p>M-Pesa B2C Payout Audit & Transaction Report</p>
    </div>

    <table class="meta-table">
        <tr>
            <td><strong>Period:</strong> {{ $from }} to {{ $to }}</td>
            <td class="text-right"><strong>Status Filter:</strong> {{ $statusLabel }}</td>
        </tr>
        <tr>
            <td><strong>Routes:</strong> {{ $routeLabel }}</td>
            <td class="text-right"><strong>Generated On:</strong> {{ now()->format('d M Y, H:i') }}</td>
        </tr>
    </table>

    <div class="summary-box">
        <strong>Summary:</strong> 
        Total Disbursed Amount: <strong>KES {{ number_format($totalAmount, 2) }}</strong> | 
        Successful: <strong>{{ $successfulCount }}</strong> | 
        Failed: <strong>{{ $failedCount }}</strong> | 
        Pending: <strong>{{ $pendingCount }}</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Farmer Name</th>
                <th>Route</th>
                <th>Phone Number</th>
                <th class="text-right">Amount (KES)</th>
                <th>Status</th>
                <th>Receipt / Error Description</th>
                <th>Transaction Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payouts as $index => $payout)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $payout->farmer->name ?? 'N/A' }}</td>
                    <td>{{ $payout->farmer->route?->name ?? 'N/A' }}</td>
                    <td>{{ $payout->phone_number }}</td>
                    <td class="text-right">{{ number_format($payout->amount, 2) }}</td>
                    <td>
                        <span class="{{ $payout->status }}">
                            {{ strtoupper($payout->status) }}
                        </span>
                    </td>
                    <td>
                        @if($payout->status === 'success')
                            {{ $payout->receipt_number ?? 'N/A' }}
                        @else
                            {{ $payout->result_desc ?? 'Failed execution' }}
                        @endif
                    </td>
                    <td>{{ $payout->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 15px;">No payout records found for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>