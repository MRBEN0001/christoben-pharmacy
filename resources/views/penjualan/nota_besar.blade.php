<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>PDF Notes</title>

    <style>
        table td {
            /* font-family: Arial, Helvetica, sans-serif; */
            font-size: 14px;
        }
        table.data td,
        table.data th {
            border: 1px solid #ccc;
            padding: 5px;
        }
        table.data {
            border-collapse: collapse;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <table width="100%">
        <tr>
            <td rowspan="4" width="60%">
                <img src="{{ public_path($setting->path_logo) }}" alt="{{ $setting->path_logo }}" width="120">
                <br>
                @if($setting->nama_perusahaan)
                    <strong>{{ strtoupper($setting->nama_perusahaan) }}</strong>
                    <br>
                @endif
                @if(!empty($setting->deskripsi_perusahaan))
                    {{ $setting->deskripsi_perusahaan }}
                    <br>
                @endif
                {{ $setting->alamat }}
                <br>
                @if(!empty($setting->telepon))
                    {{ $setting->telepon }}
                    <br>
                @endif
                <br>
            </td>
            <td>Date</td>
            <td>: {{ tanggal_indonesia(date('Y-m-d')) }}</td>
        </tr>
        <tr>
            <td>Receipt ID</td>
            <td>: {{ $penjualan->receipt_number }}</td>
        </tr>
        @if($penjualan->room_unique_details)
        <tr>
            <td>Name</td>
            <td>: {{ $penjualan->room_unique_details }}</td>
        </tr>
        @endif
    </table>

    <table class="data" width="100%">
        <thead>
            <tr>
                <th>#</th>
                <th>Code</th>
                <th>Name</th>
                <th>Unit Price</th>
                <th>Quantity</th>
                <th>Discount</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detail as $key => $item)
                <tr>
                    <td class="text-center">{{ $key+1 }}</td>
                    <td>{{ $item->produk->nama_produk }}</td>
                    <td>{{ $item->produk->kode_produk }}</td>
                    <td class="text-right">{{ format_uang($item->harga_jual) }}</td>
                    <td class="text-right">{{ format_uang($item->jumlah) }}</td>
                    <td class="text-right">{{ format_uang($item->diskon) }}</td>
                    <td class="text-right">{{ format_uang($item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right"><b>Total Price</b></td>
                <td class="text-right"><b>{{ format_uang($penjualan->total_harga) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Discount</b></td>
                <td class="text-right"><b>{{ format_uang($penjualan->diskon) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Total Pay</b></td>
                <td class="text-right"><b>{{ format_uang($penjualan->bayar) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Received</b></td>
                <td class="text-right"><b>{{ format_uang($penjualan->diterima) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Return</b></td>
                <td class="text-right"><b>{{ format_uang($penjualan->diterima - $penjualan->bayar) }}</b></td>
            </tr>
        </tfoot>
    </table>

    <table width="100%">
        <tr>
            <td><b>Thank you for shopping. We hope to see you again!</b></td>
            <td class="text-center">
                Cashier
                <br>
                <br>
                {{ auth()->user()->name }}
            </td>
        </tr>
    </table>
</body>
</html>