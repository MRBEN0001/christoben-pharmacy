<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8">
    <title>Weekly Sales Report - {{ $weekLabel }}</title>
    <style>
        body { font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 5px 0; }
        .header h4 { margin: 5px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th, table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary-box { background-color: #f9f9f9; padding: 15px; border: 2px solid #333; margin-top: 20px; }
        .grand-total { font-size: 18px; font-weight: bold; color: #000; }
    </style>
</head>
<body>
    <div class="header">
        @include('partials.company-letterhead')
        <h2>WEEKLY SALES REPORT</h2>
        <p><strong>Period:</strong> {{ tanggal_indonesia($startDate, false) }} to {{ tanggal_indonesia($endDate, false) }}</p>
        <h4>{{ $generatedAt }}</h4>
        @if(!empty($sectionName))
        <p><strong>Section:</strong> {{ $sectionName }}</p>
        @endif
    </div>

    @if($transactions->count() > 0)
    <h3>Transaction Details</h3>
    <table>
        <thead>
            <tr>
                <th width="3%">#</th>
                <th width="12%">Date &amp; Time</th>
                <th width="15%">Receipt ID</th>
                <th width="20%">Products</th>
                <th width="12%">Name</th>
                <th width="8%">Quantity</th>
                <th width="10%">Total Price</th>
                <th width="8%">Discount</th>
                <th width="12%">Total Pay</th>
                <th width="10%">Cashier</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach ($transactions as $transaction)
                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td>{{ tanggal_indonesia($transaction->created_at, false) }}, {{ \Carbon\Carbon::parse($transaction->created_at)->format('h:i A') }}</td>
                    <td>{{ $transaction->receipt_number ?? '-' }}</td>
                    <td>
                        @php
                            $productNames = $transaction->detail->map(function ($detail) {
                                return $detail->produk ? $detail->produk->nama_produk : 'N/A';
                            })->filter()->unique()->take(3)->implode(', ');
                            if ($transaction->detail->count() > 3) {
                                $productNames .= '... (+' . ($transaction->detail->count() - 3) . ' more)';
                            }
                        @endphp
                        {{ $productNames ?: '-' }}
                    </td>
                    <td>{{ $transaction->room_unique_details ?? '-' }}</td>
                    <td class="text-center">{{ format_uang($transaction->total_item) }}</td>
                    <td class="text-right">{{ format_uang($transaction->total_harga) }}</td>
                    <td class="text-center">{{ format_uang($transaction->diskon) }}</td>
                    <td class="text-right">NGN {{ format_uang($transaction->bayar) }}</td>
                    <td>{{ $transaction->user ? $transaction->user->name : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div style="padding: 20px; text-align: center;">
        <p><strong>No transactions found for this period.</strong></p>
    </div>
    @endif

    @if(count($productSummary) > 0)
    <h3>Product Summary</h3>
    <table>
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="60%">Product Name</th>
                <th width="15%">Total Quantity</th>
                <th width="20%">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @php $productNo = 1; @endphp
            @foreach ($productSummary as $productName => $summary)
                <tr>
                    <td class="text-center">{{ $productNo++ }}</td>
                    <td>{{ $productName }}</td>
                    <td class="text-center">{{ format_uang($summary['quantity']) }}</td>
                    <td class="text-right">NGN {{ format_uang($summary['total']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="summary-box">
        <table style="border: none; width: 100%;">
            <tr>
                <td style="border: none; width: 70%;"><strong>Total Transactions:</strong></td>
                <td style="border: none; text-align: right;"><strong>{{ format_uang($totalTransactions) }}</strong></td>
            </tr>
            <tr>
                <td style="border: none;"><strong>Total Items Sold:</strong></td>
                <td style="border: none; text-align: right;"><strong>{{ format_uang($totalItems) }}</strong></td>
            </tr>
            <tr>
                <td style="border: none; font-size: 16px;" class="grand-total">GRAND TOTAL:</td>
                <td style="border: none; text-align: right; font-size: 16px;" class="grand-total">NGN {{ format_uang($grandTotal) }}</td>
            </tr>
        </table>
    </div>

</body>
</html>
