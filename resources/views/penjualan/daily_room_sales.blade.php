@extends('layouts.master')

@section('title')
    Daily Room Sales
@endsection

@section('breadcrumb')
    @parent
    <li class="active">Daily Room Sales</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <ul class="nav nav-tabs">
            <li><a href="{{ route('penjualan.index') }}">Sales List</a></li>
            <li><a href="{{ route('penjualan.daily_sales') }}">Daily Sales</a></li>
            <li class="active"><a href="{{ route('penjualan.daily_room_sales') }}">Daily Room Sales</a></li>
        </ul>
    </div>
</div>
<br>

<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Daily Room Sales Report</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('penjualan.daily_room_sales_pdf', ['date' => $selectedDate]) }}" class="btn btn-success btn-flat" target="_blank">
                        <i class="fa fa-download"></i> Download PDF
                    </a>
                </div>
            </div>
            <div class="box-body">
                <form method="GET" action="{{ route('penjualan.daily_room_sales') }}" id="filter-form">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="date">Select Date</label>
                                <input type="date" name="date" id="date" class="form-control" value="{{ $selectedDate }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label><br>
                                <button type="submit" class="btn btn-primary btn-flat">
                                    <i class="fa fa-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <hr>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th>Time</th>
                                <th>Products</th>
                                <th>Name</th>
                                <th>Receipt ID</th>
                                <th>Quantity</th>
                                <th>Total Price</th>
                                <th>Discount</th>
                                <th>Total Pay</th>
                                <th>Cashier</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($transactions->count() > 0)
                                @foreach($transactions as $key => $transaction)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ date('h:i:s A', strtotime($transaction->created_at)) }}</td>
                                    <td>
                                        @php
                                            $productNames = '';
                                            if ($transaction->detail && $transaction->detail->count() > 0) {
                                                $productNames = $transaction->detail->filter(function($detail) {
                                                    return $detail->produk !== null;
                                                })->map(function($detail) {
                                                    return $detail->produk->nama_produk ?? 'N/A';
                                                })->filter()->unique()->take(3)->implode(', ');
                                                if ($transaction->detail->count() > 3) {
                                                    $productNames .= '... (+' . ($transaction->detail->count() - 3) . ' more)';
                                                }
                                            }
                                        @endphp
                                        {{ $productNames ?: '-' }}
                                    </td>
                                    <td>{{ $transaction->room_unique_details ?? '-' }}</td>
                                    <td>{{ $transaction->receipt_number ?? '-' }}</td>
                                    <td class="text-center">{{ format_uang($transaction->total_item) }}</td>
                                    <td class="text-right">₦ {{ format_uang($transaction->total_harga) }}</td>
                                    <td class="text-center">{{ format_uang($transaction->diskon) }}</td>
                                    <td class="text-right">₦ {{ format_uang($transaction->bayar) }}</td>
                                    <td>{{ $transaction->user->name ?? '-' }}</td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="10" class="text-center">
                                        <strong>No room sales found for {{ tanggal_indonesia($selectedDate, false) }}</strong>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="8" class="text-right" style="font-size: 16px;">GRAND TOTAL:</th>
                                <th class="text-right" style="font-size: 16px; color: #3c8dbc;">₦ {{ format_uang($grandTotal) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        // Auto-submit on date change
        $('#date').on('change', function() {
            $('#filter-form').submit();
        });
    });
</script>
@endpush

