<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8">
    <title>Stock Count - {{ $stockDate }}</title>
    <style>
        body {
            font-size: 11px;
            font-family: DejaVu Sans, sans-serif;
        }
        .header {
            text-align: center;
            margin-bottom: 18px;
        }
        .header h1 {
            margin: 4px 0;
            font-size: 20px;
        }
        .header h2 {
            margin: 4px 0;
            font-size: 14px;
            color: #444;
        }
        .meta {
            margin-bottom: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            border: 1px solid #333;
            padding: 6px 5px;
            text-align: left;
        }
        table th {
            background-color: #eee;
            font-weight: bold;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .summary {
            margin-top: 16px;
            padding: 10px;
            border: 1px solid #333;
            background-color: #f9f9f9;
        }
        .low-stock {
            color: #8a6d3b;
            font-weight: bold;
        }
        .sold-out {
            color: #a94442;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        @include('partials.company-letterhead', ['nameSize' => '20px'])
        <h2>STOCK COUNT REPORT</h2>
        <div>Date: {{ \Carbon\Carbon::parse($stockDate)->format('d M Y') }}</div>
        <div>Generated: {{ now()->format('d M Y h:i A') }}</div>
    </div>

    <div class="meta">
        <strong>Total Products:</strong> {{ $products->count() }} &nbsp;|&nbsp;
        <strong>Total Units in Stock:</strong> {{ number_format($totalStock) }}
    </div>

    <table>
        <thead>
            <tr>
                <th width="4%" class="text-center">#</th>
                <th width="22%">Product Name</th>
                <th width="12%">Section</th>
                <th width="12%">Category</th>
                <th width="10%">Brand</th>
                <th width="8%" class="text-center">Stock</th>
                <th width="14%">Expiry Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $index => $product)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $product->nama_produk }}</td>
                    <td>{{ $product->nama_section ?: '—' }}</td>
                    <td>{{ $product->nama_kategori ?: '—' }}</td>
                    <td>{{ $product->merk ?: '—' }}</td>
                    <td class="text-center {{ $product->stok == 0 ? 'sold-out' : ($product->stok <= 3 ? 'low-stock' : '') }}">
                        {{ $product->stok }}
                    </td>
                    <td>
                        @if($product->expiry_date)
                            {{ \Carbon\Carbon::parse($product->expiry_date)->format('d M Y') }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No products found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary">
        <strong>Summary:</strong>
        {{ $products->count() }} product(s) &nbsp;|&nbsp;
        {{ number_format($totalStock) }} total unit(s) in stock
    </div>
</body>
</html>
