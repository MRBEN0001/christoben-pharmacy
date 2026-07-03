@extends('layouts.master')

@section('title')
    Product List
@endsection

@section('breadcrumb')
    @parent
    <li class="active">Product List</li>
@endsection

@section('content')
<style>
    .product-dt-toolbar {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: 12px;
        margin-bottom: 10px;
        width: 100%;
    }
    .product-dt-toolbar .dataTables_length,
    .product-dt-toolbar .dataTables_filter {
        float: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .product-dt-toolbar .dataTables_length label,
    .product-dt-toolbar .dataTables_filter label {
        margin-bottom: 0;
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        font-weight: normal;
    }
    .product-dt-toolbar .dataTables_filter {
        margin-left: auto !important;
    }
    .product-dt-toolbar .product-filter-select {
        width: auto;
        min-width: 130px;
        display: inline-block;
    }
    .product-filter-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .product-filter-wrap .filter-alert-dot {
        display: none;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .product-filter-wrap.filter-alert-blink .filter-alert-dot {
        display: inline-block;
        background: #d9534f;
        animation: dot-blink-red 0.8s ease-in-out infinite;
    }
    .product-filter-wrap.filter-alert-blink .product-filter-select {
        animation: field-blink-red 0.8s ease-in-out infinite;
        border: 2px solid #d9534f !important;
        color: #a94442;
        font-weight: 600;
    }
    .product-filter-wrap.filter-warning-blink .filter-alert-dot {
        display: inline-block;
        background: #f0ad4e;
        animation: dot-blink-yellow 0.8s ease-in-out infinite;
    }
    .product-filter-wrap.filter-warning-blink .product-filter-select {
        animation: field-blink-yellow 0.8s ease-in-out infinite;
        border: 2px solid #f0ad4e !important;
        color: #8a6d3b;
        font-weight: 600;
    }
    @keyframes field-blink-red {
        0%, 100% {
            background-color: #fff;
            box-shadow: 0 0 0 0 rgba(217, 83, 79, 0.6);
        }
        50% {
            background-color: #f2dede;
            box-shadow: 0 0 12px 4px rgba(217, 83, 79, 0.85);
        }
    }
    @keyframes dot-blink-red {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
            box-shadow: 0 0 6px 2px rgba(217, 83, 79, 0.8);
        }
        50% {
            opacity: 0.4;
            transform: scale(1.3);
            box-shadow: 0 0 14px 5px rgba(217, 83, 79, 1);
        }
    }
    @keyframes field-blink-yellow {
        0%, 100% {
            background-color: #fff;
            box-shadow: 0 0 0 0 rgba(240, 173, 78, 0.6);
        }
        50% {
            background-color: #fcf8e3;
            box-shadow: 0 0 12px 4px rgba(240, 173, 78, 0.9);
        }
    }
    @keyframes dot-blink-yellow {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
            box-shadow: 0 0 6px 2px rgba(240, 173, 78, 0.8);
        }
        50% {
            opacity: 0.4;
            transform: scale(1.3);
            box-shadow: 0 0 14px 5px rgba(240, 173, 78, 1);
        }
    }
</style>
<div class="row">
    <div class="col-lg-12">
        <div class="box">
            @if(auth()->user()->level == 1)
            <div class="box-header with-border">
                <div class="btn-group">
                    <button onclick="addForm('{{ route('produk.store') }}')" class="btn btn-success  btn-flat"><i class="fa fa-plus-circle"></i> Add New Product</button>
                    <button onclick="deleteSelected('{{ route('produk.delete_selected') }}')" class="btn btn-danger  btn-flat"><i class="fa fa-trash"></i> Delete</button>
                    {{-- <button onclick="cetakBarcode('{{ route('produk.cetak_barcode') }}')" class="btn btn-warning  btn-flat"><i class="fa fa-barcode"></i> Print Barcode</button> --}}
                </div>

               

                <form id="barcodeForm" action="{{ route('produk.cetak_barcode') }}" method="POST" target="_blank" style="margin-bottom: 15px;">
                    @csrf
                    <input type="hidden" name="id_produk[]" id="selectedProducts">
                    <button type="button" class="btn btn-primary" onclick="submitBarcodeForm()">
                        <i class="fa fa-barcode"></i> Print Barcode
                    </button>
                    <a href="{{ route('produk.take_stock') }}" class="btn btn-info btn-flat">
                        <i class="fa fa-file-pdf-o"></i> Take Stock
                    </a>
                </form>
                
                
            </div>
            @else
            <div class="box-header with-border">
                <div class="btn-group">
                    <button onclick="addForm('{{ route('produk.add_product') }}')" class="btn btn-success btn-flat"><i class="fa fa-plus-circle"></i> Add New Product</button>
                    <a href="{{ route('produk.take_stock') }}" class="btn btn-info btn-flat">
                        <i class="fa fa-file-pdf-o"></i> Take Stock
                    </a>
                </div>
            </div>
            @endif
            <div class="box-body table-responsive">
                <div id="product-filters" class="hide">
                    <select id="filter-section" class="form-control input-sm product-filter-select">
                        <option value="">All Sections</option>
                        @foreach ($sections as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <div id="expired-filter-wrap" class="product-filter-wrap {{ $expiredCount > 0 ? 'filter-alert-blink' : '' }}">
                        <span class="filter-alert-dot" title="{{ $expiredCount }} expired product(s)"></span>
                        <select id="filter-expired" class="form-control input-sm product-filter-select" title="Filter expired products">
                            <option value="">Expired: All</option>
                            <option value="expired">Expired ({{ $expiredCount }})</option>
                        </select>
                    </div>
                    <div id="soon-to-expire-filter-wrap" class="product-filter-wrap {{ $soonToExpireCount > 0 ? 'filter-warning-blink' : '' }}">
                        <span class="filter-alert-dot" title="{{ $soonToExpireCount }} product(s) expiring within 180 days"></span>
                        <select id="filter-soon-to-expire" class="form-control input-sm product-filter-select" title="Filter products expiring within 180 days">
                            <option value="">Soon Expire: All</option>
                            <option value="soon_to_expire">Soon to Expire ({{ $soonToExpireCount }})</option>
                        </select>
                    </div>
                    <div id="out-of-stock-filter-wrap" class="product-filter-wrap {{ $outOfStockCount > 0 ? 'filter-alert-blink' : '' }}">
                        <span class="filter-alert-dot" title="{{ $outOfStockCount }} out of stock product(s)"></span>
                        <select id="filter-out-of-stock" class="form-control input-sm product-filter-select" title="Filter out of stock products">
                            <option value="">Out of Stock: All</option>
                            <option value="out_of_stock">Out of Stock ({{ $outOfStockCount }})</option>
                        </select>
                    </div>
                    <div id="soon-out-of-stock-filter-wrap" class="product-filter-wrap {{ $soonOutOfStockCount > 0 ? 'filter-warning-blink' : '' }}">
                        <span class="filter-alert-dot" title="{{ $soonOutOfStockCount }} product(s) with 3 or less in stock"></span>
                        <select id="filter-soon-out-of-stock" class="form-control input-sm product-filter-select" title="Filter products with 3 or less in stock">
                            <option value="">Soon Out: All</option>
                            <option value="soon_out_of_stock">Soon Out of Stock ({{ $soonOutOfStockCount }})</option>
                        </select>
                    </div>
                    <select id="filter-category" class="form-control input-sm product-filter-select">
                        <option value="">All Categories</option>
                        @foreach ($kategori as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <form action="" method="post" class="form-produk">
                    @csrf
                    <table class="table table-stiped table-bordered table-hover">
                        <thead>
                            <tr>
                            @if(auth()->user()->level == 1)
                            <th width="5%">
                                <input type="checkbox" name="select_all" id="select_all">
                            </th>
                            @endif
                            <th width="5%">#</th>
                            <th>Code</th>
                            <th>Barcode</th>
                            <th>Name</th>
                            <th>Section</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Purchase Price</th>
                            <th>Selling Price</th>
                            <th>Discount</th>
                            <th>Stock</th>
                            <th>Expiry Date</th>
                            <th width="15%"><i class="fa fa-cog"></i></th>
                            </tr>
                        </thead>
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>

@includeIf('produk.form')

<!-- Add Stock Modal -->
<div class="modal fade" id="modal-add-stock" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Add Stock</h4>
            </div>
            <div class="modal-body">
                <form id="form-add-stock">
                    @csrf
                    <input type="hidden" id="add-stock-product-id" name="product_id">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" id="add-stock-product-name" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label>Current Stock</label>
                        <input type="text" id="add-stock-current-stock" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label for="stock_to_add">Stock to Add <span class="text-danger">*</span></label>
                        <input type="number" name="stock_to_add" id="stock_to_add" class="form-control" min="1" required autofocus>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-flat" onclick="submitAddStock()">Add Stock</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let table;

    function updateAlertIndicators(expiredCount, outOfStockCount, soonToExpireCount, soonOutOfStockCount) {
        var $expiredWrap = $('#expired-filter-wrap');
        var $soonToExpireWrap = $('#soon-to-expire-filter-wrap');
        var $outOfStockWrap = $('#out-of-stock-filter-wrap');
        var $soonOutOfStockWrap = $('#soon-out-of-stock-filter-wrap');

        $expiredWrap.toggleClass('filter-alert-blink', expiredCount > 0);
        $expiredWrap.find('.filter-alert-dot').attr('title', expiredCount + ' expired product(s)');
        $('#filter-expired option[value="expired"]').text('Expired (' + expiredCount + ')');

        $soonToExpireWrap.toggleClass('filter-warning-blink', soonToExpireCount > 0);
        $soonToExpireWrap.find('.filter-alert-dot').attr('title', soonToExpireCount + ' product(s) expiring within 180 days');
        $('#filter-soon-to-expire option[value="soon_to_expire"]').text('Soon to Expire (' + soonToExpireCount + ')');

        $outOfStockWrap.toggleClass('filter-alert-blink', outOfStockCount > 0);
        $outOfStockWrap.find('.filter-alert-dot').attr('title', outOfStockCount + ' out of stock product(s)');
        $('#filter-out-of-stock option[value="out_of_stock"]').text('Out of Stock (' + outOfStockCount + ')');

        $soonOutOfStockWrap.toggleClass('filter-warning-blink', soonOutOfStockCount > 0);
        $soonOutOfStockWrap.find('.filter-alert-dot').attr('title', soonOutOfStockCount + ' product(s) with 3 or less in stock');
        $('#filter-soon-out-of-stock option[value="soon_out_of_stock"]').text('Soon Out of Stock (' + soonOutOfStockCount + ')');
    }

    $(function () {
        @php
            $isAdmin = auth()->user()->level == 1;
            $columns = [];
            if ($isAdmin) {
                $columns[] = ['data' => 'select_all', 'searchable' => false, 'sortable' => false];
            }
            $columns = array_merge($columns, [
                ['data' => 'DT_RowIndex', 'searchable' => false, 'sortable' => false],
                ['data' => 'kode_produk'],
                ['data' => 'barcode'],
                ['data' => 'nama_produk'],
                ['data' => 'nama_section'],
                ['data' => 'nama_kategori'],
                ['data' => 'merk'],
                ['data' => 'harga_beli'],
                ['data' => 'harga_jual'],
                ['data' => 'diskon'],
                ['data' => 'stok'],
                ['data' => 'expiry_date'],
                ['data' => 'aksi', 'searchable' => false, 'sortable' => false],
            ]);
        @endphp
        
        table = $('.table').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            autoWidth: false,
            ajax: {
                url: '{{ route('produk.data') }}',
                data: function (d) {
                    d.filter_expired = $('#filter-expired').val();
                    d.filter_soon_to_expire = $('#filter-soon-to-expire').val();
                    d.filter_out_of_stock = $('#filter-out-of-stock').val();
                    d.filter_soon_out_of_stock = $('#filter-soon-out-of-stock').val();
                    d.filter_section = $('#filter-section').val();
                    d.filter_category = $('#filter-category').val();
                }
            },
            columns: @json($columns),
            language: {
                search: "Search all columns:",
                lengthMenu: "Show _MENU_"
            },
            initComplete: function () {
                var $wrapper = $('.table').closest('.dataTables_wrapper');
                var $row = $wrapper.find('> .row').first();
                var $length = $row.find('.dataTables_length').first();
                var $filter = $row.find('.dataTables_filter').first();
                var $section = $('#filter-section');
                var $expiredWrap = $('#expired-filter-wrap');
                var $soonToExpireWrap = $('#soon-to-expire-filter-wrap');
                var $outOfStockWrap = $('#out-of-stock-filter-wrap');
                var $soonOutOfStockWrap = $('#soon-out-of-stock-filter-wrap');
                var $category = $('#filter-category');

                var $toolbar = $('<div class="product-dt-toolbar"></div>');
                $toolbar.append($length);
                $toolbar.append($section);
                $toolbar.append($expiredWrap);
                $toolbar.append($soonToExpireWrap);
                $toolbar.append($outOfStockWrap);
                $toolbar.append($soonOutOfStockWrap);
                $toolbar.append($category);
                $toolbar.append($filter);

                $row.empty().append($('<div class="col-sm-12"></div>').append($toolbar));
            }
        });

        table.on('xhr.dt', function (e, settings, json) {
            if (json && typeof json.expired_count !== 'undefined') {
                updateAlertIndicators(
                    json.expired_count,
                    json.out_of_stock_count,
                    json.soon_to_expire_count,
                    json.soon_out_of_stock_count
                );
            }
        });

        $('#modal-form').validator().on('submit', function (e) {
            if (! e.preventDefault()) {
                $.post($('#modal-form form').attr('action'), $('#modal-form form').serialize())
                    .done((response) => {
                        $('#modal-form').modal('hide');
                        table.ajax.reload();
                    })
                    .fail((errors) => {
                        alert('Unable to save data');
                        return;
                    });
            }
        });

        $('[name=select_all]').on('click', function () {
            $(':checkbox').prop('checked', this.checked);
        });

        $('#filter-section, #filter-expired, #filter-soon-to-expire, #filter-out-of-stock, #filter-soon-out-of-stock, #filter-category').on('change', function () {
            table.ajax.reload();
        });
    });

    function addForm(url) {
        $('#modal-form').modal('show');
        $('#modal-form .modal-title').text('Add Product');

        $('#modal-form form')[0].reset();
        $('#modal-form form').attr('action', url);
        $('#modal-form [name=_method]').val('post');
        $('#modal-form [name=nama_produk]').focus();
    }

    function editForm(url) {
        $('#modal-form').modal('show');
        $('#modal-form .modal-title').text('Edit Product');

        $('#modal-form form')[0].reset();
        $('#modal-form form').attr('action', url);
        $('#modal-form [name=_method]').val('put');
        $('#modal-form [name=nama_produk]').focus();

        $.get(url)
            .done((response) => {
                $('#modal-form [name=nama_produk]').val(response.nama_produk);
                $('#modal-form [name=barcode]').val(response.barcode);
                $('#modal-form [name=id_section]').val(response.id_section);
                $('#modal-form [name=id_kategori]').val(response.id_kategori);
                $('#modal-form [name=merk]').val(response.merk);
                $('#modal-form [name=harga_beli]').val(response.harga_beli);
                $('#modal-form [name=harga_jual]').val(response.harga_jual);
                $('#modal-form [name=diskon]').val(response.diskon);
                $('#modal-form [name=stok]').val(response.stok);
                $('#modal-form [name=expiry_date]').val(response.expiry_date);
            })
            .fail((errors) => {
                alert('Unable to display data');
                return;
            });
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

    function deleteSelected(url) {
        if ($('input:checked').length > 1) {
            if (confirm('Yakin ingin menghapus data terpilih?')) {
                $.post(url, $('.form-produk').serialize())
                    .done((response) => {
                        table.ajax.reload();
                    })
                    .fail((errors) => {
                        alert('Unable to delete data');
                        return;
                    });
            }
        } else {
            alert('Select the data to delete');
            return;
        }
    }

    function cetakBarcode(url) {
        if ($('input:checked').length < 1) {
            alert('Select the data to print');
            return;
        } else if ($('input:checked').length < 3) {
            alert('Select at least 3 data to print');
            return;
        } else {
            $('.form-produk')
                .attr('target', '_blank')
                .attr('action', url)
                .submit();
        }


    }



    
    function submitBarcodeForm() {
    const selected = $('input[name="id_produk[]"]:checked')
        .map(function () { return this.value; })
        .get();

    if (selected.length === 0) {
        alert('Please select at least one product to print.');
        return;
    }

    // Dynamically build hidden inputs
    const form = $('#barcodeForm');
    form.find('input[name="id_produk[]"]').remove(); // clear previous ones

    selected.forEach(id => {
        form.append(`<input type="hidden" name="id_produk[]" value="${id}">`);
    });

    form.submit();
}

    function showAddStockForm(id, name, currentStock) {
        $('#add-stock-product-id').val(id);
        $('#add-stock-product-name').val(name);
        $('#add-stock-current-stock').val(currentStock);
        $('#stock_to_add').val('');
        $('#modal-add-stock').modal('show');
        $('#stock_to_add').focus();
    }

    function submitAddStock() {
        let productId = $('#add-stock-product-id').val();
        let stockToAdd = $('#stock_to_add').val();

        if (!stockToAdd || stockToAdd < 1) {
            alert('Please enter a valid stock amount (minimum 1)');
            return;
        }

        $.post(`{{ url('/produk') }}/${productId}/add-stock`, {
            '_token': $('[name=csrf-token]').attr('content'),
            'stock_to_add': stockToAdd
        })
        .done((response) => {
            $('#modal-add-stock').modal('hide');
            table.ajax.reload();
            alert('Stock added successfully! New stock: ' + response.new_stock);
        })
        .fail((errors) => {
            let errorMessage = 'Unable to add stock';
            if (errors.responseJSON && errors.responseJSON.message) {
                errorMessage = errors.responseJSON.message;
            }
            alert(errorMessage);
        });
    }

</script>
@endpush