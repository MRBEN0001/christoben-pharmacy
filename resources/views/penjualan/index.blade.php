@extends('layouts.master')

@section('title')
    Sales List
@endsection

@section('breadcrumb')
    @parent
    <li class="active">Sales List</li>
@endsection

@section('content')
<style>
    .sales-report-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }
    .sales-report-toolbar select {
        min-width: 180px;
    }
</style>

<div class="row">
    <div class="col-lg-12">
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                {{ session('error') }}
            </div>
        @endif
        <div class="sales-report-toolbar">
            <select id="report-section" class="form-control input-sm">
                <option value="">All Sections</option>
                @foreach ($sections as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            <a href="#" onclick="downloadSalesReport('daily'); return false;" class="btn btn-success btn-flat">
                <i class="fa fa-download"></i> Daily Report
            </a>
            <a href="#" onclick="downloadSalesReport('weekly'); return false;" class="btn btn-primary btn-flat">
                <i class="fa fa-download"></i> Weekly Report
            </a>
            <a href="#" onclick="downloadSalesReport('monthly'); return false;" class="btn btn-info btn-flat">
                <i class="fa fa-download"></i> Monthly Report
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-body table-responsive">
                <table class="table table-stiped table-bordered table-penjualan table-hover">
                    <thead>
                        <th width="5%">#</th>
                        <th>Date</th>
                        <th>Section</th>
                        <th>Products</th>
                        <th>Category</th>
                        <th>Name</th>
                        <th>Phone Number</th>
                        <th>Receipt ID</th>
                        <th>Quantity</th>
                        <th>Total Price</th>
                        <th>Discount</th>
                        <th>Total Pay</th>
                        <th>Cashier</th>
                        <th width="15%"><i class="fa fa-cog"></i></th>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

@includeIf('penjualan.detail')
@endsection

@push('scripts')
<script>
    let table, table1;

    function downloadSalesReport(type) {
        let section = $('#report-section').val();
        let url = '';

        if (type === 'daily') {
            url = '{{ route('penjualan.daily_sales_pdf') }}?date={{ date('Y-m-d') }}';
        } else if (type === 'weekly') {
            url = '{{ route('penjualan.weekly_report') }}?startDate={{ $weekStartDate }}&endDate={{ $weekEndDate }}';
        } else {
            url = '{{ route('penjualan.monthly_report') }}?startDate={{ $startDate }}&endDate={{ $endDate }}';
        }

        if (section) {
            url += '&section=' + encodeURIComponent(section);
        }

        window.location.href = url;
    }

    $(function () {
        table = $('.table-penjualan').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            autoWidth: false,
            ajax: {
                url: '{{ route('penjualan.data') }}',
                data: function (d) {
                    d.section = $('#report-section').val();
                }
            },
            columns: [
                {data: 'DT_RowIndex', searchable: false, sortable: false},
                {data: 'tanggal'},
                {data: 'section'},
                {data: 'products'},
                {data: 'category'},
                {data: 'room_details'},
                {data: 'phone_number'},
                {data: 'receipt_number'},
                {data: 'total_item'},
                {data: 'total_harga'},
                {data: 'diskon'},
                {data: 'bayar'},
                {data: 'kasir'},
                {data: 'aksi', searchable: false, sortable: false},
            ]
        });

        $('#report-section').on('change', function () {
            table.ajax.reload();
        });

        table1 = $('.table-detail').DataTable({
            processing: true,
            bSort: false,
            dom: 'Brt',
            columns: [
                {data: 'DT_RowIndex', searchable: false, sortable: false},
                {data: 'kode_produk'},
                {data: 'nama_produk'},
                {data: 'harga_jual'},
                {data: 'jumlah'},
                {data: 'diskon'},
                {data: 'subtotal'},
            ]
        })
    });

    function showDetail(url) {
        let section = $('#report-section').val();
        if (section) {
            url += (url.indexOf('?') > -1 ? '&' : '?') + 'section=' + encodeURIComponent(section);
        }

        $('#modal-detail').modal('show');

        table1.ajax.url(url);
        table1.ajax.reload();
    }

    function deleteData(url) {
        if (confirm('Are you sure you want to delete selected data?')) {
            $.post(url, {
                    '_token': $('[name=csrf-token]').attr('content'),
                    '_method': 'delete'
                })
                .done((response) => {
                    table.ajax.reload();
                })
                .fail((errors) => {
                    alert('Unable to delete data');
                    return;
                });
        }
    }
</script>
@endpush
