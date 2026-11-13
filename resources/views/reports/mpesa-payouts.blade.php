<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>M-Pesa B2C Payout Report</title>
    {{-- DomPDF supports only simple CSS: no flexbox or grid, so layout uses tables. --}}
    <style>
        @page {
            margin: 28px 30px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1f2937;
        }

        h1 {
            font-size: 16px;
            margin: 0;
        }

        h2 {
            font-size: 11px;
            margin: 2px 0 10px;
            color: #4b5563;
            font-weight: normal;
        }

        .meta {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }

        .meta td {
            padding: 2px 0;
            font-size: 9px;
            color: #374151;
        }

        /* Summary boxes */
        .summary {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin: 0 -6px 12px;
        }

        .summary td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            width: 20%;
            vertical-align: top;
        }

        .summary .label {
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
        }

        .summary .value {
            font-size: 12px;
            font-weight: bold;
            margin-top: 2px;
        }

        .summary .sub {
            font-size: 8px;
            color: #6b7280;
            margin-top: 2px;
        }

        .green {
            color: #047857;
        }

        .amber {
            color: #b45309;
        }

        .red {
            color: #b91c1c;
        }

        /* Main table */
        table.rows {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.rows th {
            background: #f3f4f6;
            text-align: left;
            padding: 5px 6px;
            border-bottom: 1px solid #9ca3af;
            font-size: 8px;
        }

        table.rows td {
            padding: 5px 6px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
            word-wrap: break-word;
        }

        table.rows thead {
            display: table-header-group;
        }

        /* repeat header on every page */
        table.rows tr {
            page-break-inside: avoid;
        }

        .num {
            text-align: right;
        }

        .badge {
            font-weight: bold;
            font-size: 8px;
            padding: 1px 5px;
            border-radius: 3px;
        }

        .b-success {
            color: #065f46;
            background: #d1fae5;
        }

        .b-pending {
            color: #92400e;
            background: #fef3c7;
        }

        .b-failed {
            color: #991b1b;
            background: #fee2e2;
        }

        .note {
            margin-top: 10px;
            font-size: 8px;
            color: #6b7280;
        }
    </style>
</head>

<body>

    <h1>Dairy Management System</h1>
    <h2>M-Pesa B2C Payout Audit &amp; Transaction Report</h2>

    <table class="meta">
        <tr>
            <td><strong>Period:</strong> {{ $from }} to {{ $to }}</td>
            <td><strong>Status Filter:</strong> {{ $statusLabel }}</td>
            <td><strong>Routes:</strong> {{ $routeLabel }}</td>
            <td><strong>Generated On:</strong> {{ now()->format('d M Y, H:i') }}</td>
        </tr>
    </table>

    {{-- Summary: only "success" money counts as disbursed. --}}
    <table class="summary">
        <tr>
            <td>
                <div class="label">Disbursed (confirmed)</div>
                <div class="value green">KES {{ number_format($totalAmount, 2) }}</div>
                <div class="sub">{{ $successfulCount }} successful</div>
            </td>
            <td>
                <div class="label">Pending confirmation</div>
                <div class="value amber">KES {{ number_format($pendingAmount, 2) }}</div>
                <div class="sub">{{ $pendingCount }} awaiting M-Pesa</div>
            </td>
            <td>
                <div class="label">Failed attempts</div>
                <div class="value red">KES {{ number_format($failedAmount, 2) }}</div>
                <div class="sub">{{ $failedCount }} attempt(s), includes retried</div>
            </td>
            <td>
                <div class="label">Failed, still unpaid</div>
                <div class="value red">KES {{ number_format($unpaidAmount, 2) }}</div>
                <div class="sub">{{ $unpaidCount }} farmer(s) need a retry</div>
            </td>
            <td>
                <div class="label">Rows in this report</div>
                <div class="value">{{ $payouts->count() }}</div>
                <div class="sub">{{ $statusLabel }}</div>
            </td>
        </tr>
    </table>

    <table class="rows">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 16%;">Farmer Name</th>
                <th style="width: 9%;">Route</th>
                <th style="width: 11%;">Phone Number</th>
                <th style="width: 10%;" class="num">Amount (KES)</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 30%;">Receipt / Description</th>
                <th style="width: 12%;">Transaction Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payouts as $payout)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $payout->farmer->name ?? $payout->farmer_id }}</td>
                <td>{{ $payout->farmer->route->name ?? '-' }}</td>
                <td>{{ $payout->phone_number }}</td>
                <td class="num">{{ number_format((float) $payout->amount, 2) }}</td>
                <td>
                    <span class="badge b-{{ $payout->status }}">{{ strtoupper($payout->status) }}</span>
                </td>
                <td>
                    @if ($payout->status === 'success')
                    {{ $payout->receipt_number ?? 'Receipt not recorded' }}
                    @elseif ($payout->status === 'pending')
                    Awaiting M-Pesa confirmation
                    @if ($payout->updated_at && $payout->updated_at->lt(now()->subMinutes(30)))
                    <br><span class="red">Stuck over 30 min: check M-Pesa statement</span>
                    @endif
                    @else
                    {{ \Illuminate\Support\Str::limit($payout->result_desc ?: 'No description', 120) }}
                    @endif
                </td>
                <td>{{ $payout->created_at?->format('d/m/Y H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 14px;">
                    No payouts found for the selected filters.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="note">
        Each retry is a separate row, so a farmer can appear more than once. Only
        "Disbursed (confirmed)" is money M-Pesa has confirmed as sent.
    </div>

</body>

</html>