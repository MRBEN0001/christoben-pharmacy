<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title> </title>

    <style>
        * {
            font-family: "consolas", sans-serif;
        }
        p {
            display: block;
            margin: 3px;
            font-size: 10pt;
        }
        table td {
            font-size: 9pt;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }

        @media print {
            @page {
                margin: 0;
                size: 75mm auto;
            }
            html, body {
                width: 70mm;
                margin: 0;
                padding: 0;
            }
            .btn-print {
                display: none;
            }
        }
    </style>
</head>
<body onload="printReceipt()">
    <button class="btn-print" style="position: absolute; right: 1rem; top: rem;" onclick="printReceipt()">Print</button>
    <div class="text-center">
        @include('partials.company-letterhead', ['variant' => 'receipt-small', 'showAddress' => true, 'uppercaseAddress' => true])
    </div>
    <br>
    <div>
        <p style="float: left;">{{ date('d-m-Y') }}</p>
        <p style="float: right;">{{ strtoupper(auth()->user()->name) }}</p>
    </div>
    <div class="clear-both" style="clear: both;"></div>
    <p>No: {{ tambah_nol_didepan($penjualan->id_penjualan, 10) }}</p>
    <p>Receipt ID: {{ $penjualan->receipt_number }}</p>
    @if($penjualan->room_unique_details)
    <p>Name: {{ strtoupper($penjualan->room_unique_details) }}</p>
    @endif
    <p class="text-center">===================================</p>
    
    <br>
    <table width="100%" style="border: 0;">
        @foreach ($detail as $item)
            <tr>
                <td colspan="3">{{ $item->produk->nama_produk }}</td>
            </tr>
            <tr>
                <td>{{ $item->jumlah }} x {{ format_uang($item->harga_jual) }}</td>
                <td></td>
                <td class="text-right">{{ format_uang($item->jumlah * $item->harga_jual) }}</td>
            </tr>
        @endforeach
    </table>
    <p class="text-center">-----------------------------------</p>

    <table width="100%" style="border: 0;">
        <tr>
            <td>Total Price:</td>
            <td class="text-right">{{ format_uang($penjualan->total_harga) }}</td>
        </tr>
        <tr>
            <td>Total Item:</td>
            <td class="text-right">{{ format_uang($penjualan->total_item) }}</td>
        </tr>
        <tr>
            <td>Discount:</td>
                <td class="text-right">{{ format_uang($penjualan->diskon) }}</td>
        </tr>
        <tr>
            <td>Total Pay:</td>
            <td class="text-right">{{ format_uang($penjualan->bayar) }}</td>
        </tr>
        <tr>
            <td>Received:</td>
            <td class="text-right">{{ format_uang($penjualan->diterima) }}</td>
        </tr>
        <tr>
            <td>Return:</td>
            <td class="text-right">{{ format_uang($penjualan->diterima - $penjualan->bayar) }}</td>
        </tr>
    </table>

    <p class="text-center">===================================</p>
    <p class="text-center">-- THANK YOU --</p>

    <script>
        function printReceipt() {
            document.title = ' ';
            window.print();
        }

        let body = document.body;
        let html = document.documentElement;
        let height = Math.max(
                body.scrollHeight, body.offsetHeight,
                html.clientHeight, html.scrollHeight, html.offsetHeight
            );

        document.cookie = "innerHeight=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        document.cookie = "innerHeight="+ ((height + 50) * 0.264583);
    </script>
</body>
</html>