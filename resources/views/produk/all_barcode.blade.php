<!DOCTYPE html>
<html>
<head>
    <title>All Product Barcodes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .barcode-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
        }
        .barcode-item {
            border: 1px solid #ddd;
            padding: 10px;
            margin: 8px;
            text-align: center;
            width: 160px;
        }
        .barcode-item img {
            margin-top: 5px;
        }
        .print-btn {
            margin-bottom: 15px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 6px;
        }
        .print-btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

    <h2>All Product Barcodes</h2>
    <button class="print-btn" onclick="window.print()">🖨️ Print Page</button>

    <div class="barcode-container">
        @foreach($dataproduk as $p)
          

            {{-- <div class=" barcode-item mb-4 p-2 border rounded text-center">
                <strong>{{ $p->nama_produk }}</strong><br>
                <span>{{ $p->kode_produk }}</span><br>
                <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG('100001', 'EAN13', 2, 100) }}" alt="barcode">
                <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($p->kode_produk, 'EAN13', 2, 100) }}" alt="barcode">

            </div> --}}



            {{-- <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="barcode-item p-3 border rounded text-center">
                        <strong>{{ $p->nama_produk }}</strong><br>
                        <span>{{ $p->kode_produk }}</span><br>
                        <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG('100001', 'EAN13', 2, 100) }}" alt="barcode">
                    </div>
                </div>
            </div> --}}

            


        @endforeach

{{-- 
        <div class="container my-4">
            <div class="row justify-content-center">
                @foreach($dataproduk as $p)
                    <div class="col-12 mb-4">
                        <div class="barcode-item p-3 border rounded text-center shadow-sm">
                            <strong>{{ $p->nama_produk }}</strong><br>
                            <span>{{ $p->kode_produk }}</span><br>
                            <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($p->kode_produk, 'EAN13', 2, 100) }}" alt="barcode">
                        </div>
                    </div>
                @endforeach
            </div>
        </div> --}}
        
        <div class="container my-4">
            <div class="row justify-content-center">
                @foreach($dataproduk as $p)
                    @php
                        $code = trim($p->barcode ?: $p->kode_produk);
                    @endphp
                    <div class="col-12 mb-4">
                        <div class="barcode-item p-3 border rounded text-center shadow-sm">
                            <strong>{{ $p->nama_produk }}</strong><br>
                            <span>{{ $code }}</span><br>
                            <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($code, 'C128', 3, 120) }}" alt="barcode">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        

    </div>

</body>
</html>
