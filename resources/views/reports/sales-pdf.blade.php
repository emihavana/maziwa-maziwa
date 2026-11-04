<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .text-right {
            text-align: right;
        }

        .total-row {
            font-weight: bold;
            background-color: #e6e6e6;
        }
    </style>
</head>

<body>
    <h2>Milk Sales Report</h2>
    <p><strong>Period:</strong> {{ $from }} to {{ $until }}</p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Quantity (Liters)</th>
                <th>Price / Liter (KSH)</th>
                <th class="text-right">Earnings (KSH)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
            <tr>
                <td>{{ \Carbon\Carbon::parse($sale->sale_date)->format('M d, Y') }}</td>
                <td>{{ number_format($sale->qty, 2) }}</td>
                <td>{{ number_format($sale->sale_price_per_liter, 2) }}</td>
                <td class="text-right">{{ number_format($sale->qty * $sale->sale_price_per_liter, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td>TOTALS</td>
                <td>{{ number_format($totalLiters, 2) }} L</td>
                <td>-</td>
                <td class="text-right">KSH {{ number_format($totalEarnings, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>

</html>