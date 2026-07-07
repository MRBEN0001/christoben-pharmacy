@extends('layouts.master')

@section('title')
Sales Transactions
@endsection

@push('css')
<style>
    .tampil-bayar {
        font-size: 5em;
        text-align: center;
        height: 100px;
    }

    .tampil-terbilang {
        padding: 10px;
        background: #f0f0f0;
    }

    .table-penjualan tbody tr:last-child {
        display: none;
    }

    @media(max-width: 768px) {
        .tampil-bayar {
            font-size: 3em;
            height: 70px;
            padding-top: 5px;
        }
    }
</style>
@endpush

@section('breadcrumb')
    @parent
    <li class="active">Sales Transactions</li>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('error') }}
    </div>
@endif
<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-body">
                    
                <form class="form-produk">
                    @csrf
                    <div class="form-group row">
                        <label for="kode_produk" class="col-lg-2">Barcode</label>
                        <div class="col-lg-5">
                            <div class="input-group">
                                <input type="hidden" name="id_penjualan" id="id_penjualan" value="{{ $id_penjualan }}">
                                <input type="hidden" name="id_produk" id="id_produk">
                                <input type="text" class="form-control" name="kode_produk" id="kode_produk" placeholder="Scan barcode to add product" autofocus>
                                <span class="input-group-btn">
                                    <button onclick="tampilProduk()" class="btn btn-success btn-flat" type="button"><i class="fa fa-search-plus"></i></button>
                                </span>
                            </div>
                        </div>
                    </div>
                </form>

                <table class="table table-stiped table-bordered table-penjualan">
                    <thead>
                        <th width="5%">#</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th width="15%">Quantity</th>
                        <th>Discount (₦)</th>
                        <th>Subtotal</th>
                        <th width="15%"><i class="fa fa-cog"></i></th>
                    </thead>
                </table>

                @if(! $isPicker)
                <div class="row">
                    <div class="col-lg-8">
                        <div class="tampil-bayar bg-primary"></div>
                        <div class="tampil-terbilang"></div>
                    </div>
                    <div class="col-lg-4">
                        <form action="{{ route('transaksi.simpan') }}" class="form-penjualan" method="post">
                            @csrf
                            <input type="hidden" name="id_penjualan" value="{{ $id_penjualan }}">
                            <input type="hidden" name="total" id="total">
                            <input type="hidden" name="total_item" id="total_item">
                            <input type="hidden" name="bayar" id="bayar">
                            <input type="hidden" name="id_member" id="id_member" value="{{ $memberSelected->id_member }}">

                            <div class="form-group row">
                                <label for="totalrp" class="col-lg-2 control-label">Total</label>
                                <div class="col-lg-8">
                                    <input type="text" id="totalrp" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="room_unique_details" class="col-lg-2 control-label">Name</label>
                                <div class="col-lg-8">
                                    <input type="text" name="room_unique_details" id="room_unique_details" class="form-control" 
                                        value="{{ $penjualan->room_unique_details ?? '' }}" 
                                        placeholder="Enter customer name">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="phone_number" class="col-lg-2 control-label">Phone Number</label>
                                <div class="col-lg-8">
                                    <input type="text" name="phone_number" id="phone_number" class="form-control" 
                                        value="{{ $penjualan->phone_number ?? '' }}" 
                                        placeholder="Customer phone (optional)">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="diskon" class="col-lg-2 control-label">Discount</label>
                                <div class="col-lg-8">
                                    <div class="input-group">
                                        <span class="input-group-addon">₦</span>
                                        <input type="number" name="diskon" id="diskon" class="form-control" 
                                            value="{{ $transactionDiskon ?? ($penjualan->diskon ?? 0) }}" 
                                            readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="bayar" class="col-lg-2 control-label">Pay</label>
                                <div class="col-lg-8">
                                    <input type="text" id="bayarrp" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="diterima" class="col-lg-2 control-label">Received</label>
                                <div class="col-lg-8">
                                    <input type="number" id="diterima" class="form-control" name="diterima" value="{{ $penjualan->diterima ?? 0 }}">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="kembali" class="col-lg-2 control-label">Return</label>
                                <div class="col-lg-8">
                                    <input type="text" id="kembali" name="kembali" class="form-control" value="0" readonly>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endif
            </div>

            <div class="box-footer">
                @if(! $isPicker)
                <button type="submit" class="btn btn-success btn-sm btn-flat pull-right btn-simpan"><i class="fa fa-shopping-cart"></i> Check Out</button>
                @else
                <form action="{{ route('transaksi.kirim') }}" method="post" class="form-kirim" onsubmit="return confirm('Send this basket to Provisions? You will not be able to edit it afterwards.');">
                    @csrf
                    <input type="hidden" name="id_penjualan" value="{{ $id_penjualan }}">
                    <span class="text-muted">Scan or select products, then send the basket to Provisions for checkout.</span>
                    <button type="submit" class="btn btn-primary btn-sm btn-flat pull-right btn-kirim"><i class="fa fa-paper-plane"></i> Send to Provisions</button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>

@includeIf('penjualan_detail.produk')
@includeIf('penjualan_detail.member')
@endsection

@push('scripts')
<script>
    let table, table2;

    $(function () {
        // Collapse sidebar for better screen space (optional)
        $('body').addClass('sidebar-collapse');

        // ✅ Initialize DataTable for sales details
        table = $('.table-penjualan').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            autoWidth: false,
            ajax: {
                url: '{{ route('transaksi.data', $id_penjualan) }}',
            },
            columns: [
                {data: 'DT_RowIndex', searchable: false, sortable: false},
                {data: 'kode_produk'},
                {data: 'nama_produk'},
                {data: 'harga_jual'},
                {data: 'jumlah'},
                {data: 'diskon'},
                {data: 'subtotal'},
                {data: 'aksi', searchable: false, sortable: false},
            ],
            dom: 'Brt',
            bSort: false,
            paginate: false
        })
        .on('draw.dt', function () {
            let totalDiskon = parseFloat($('.total_diskon').text()) || 0;
            $('#diskon').val(totalDiskon);
            loadForm(0);
            setTimeout(() => {
                $('#diterima').trigger('input');
            }, 300);
        });

        table2 = $('.table-produk').DataTable();

        // ✅ Auto-focus the barcode field on page load
        $('#kode_produk').focus();

        // ✅ When quantity input changes
        $(document).on('input', '.quantity', function () {
            let id = $(this).data('id');
            let jumlah = parseInt($(this).val());

            if (jumlah < 1) {
                $(this).val(1);
                alert('The number cannot be less than 1');
                return;
            }
            if (jumlah > 10000) {
                $(this).val(10000);
                alert('The number cannot exceed 10000');
                return;
            }

            $.post(`{{ url('/transaksi') }}/${id}`, {
                    '_token': $('[name=csrf-token]').attr('content'),
                    '_method': 'put',
                    'jumlah': jumlah
                })
                .done(response => {
                    $(this).on('mouseout', function () {
                        table.ajax.reload(() => loadForm(0));
                    });
                })
                .fail(errors => {
                    let message = 'Unable to save data';
                    if (errors.responseJSON && errors.responseJSON.message) {
                        message = errors.responseJSON.message;
                    }
                    alert(message);
                    table.ajax.reload(() => loadForm(0));
                    return;
                });
        });

        // ✅ When discount input changes
        $(document).on('input', '.discount-input', function () {
            let id = $(this).data('id');
            let diskon = parseFloat($(this).val()) || 0;
            let maxDiskon = parseFloat($(this).data('max')) || 0;

            if (diskon < 0) {
                $(this).val(0);
                diskon = 0;
            }
            if (maxDiskon > 0 && diskon > maxDiskon) {
                $(this).val(maxDiskon);
                diskon = maxDiskon;
            }

            $.post(`{{ url('/transaksi') }}/${id}`, {
                    '_token': $('[name=csrf-token]').attr('content'),
                    '_method': 'put',
                    'diskon': diskon
                })
                .done(response => {
                    // Reload table to update subtotals and totals
                    table.ajax.reload(() => loadForm(0));
                })
                .fail(errors => {
                    let message = 'Unable to save discount';
                    if (errors.responseJSON && errors.responseJSON.message) {
                        message = errors.responseJSON.message;
                    }
                    alert(message);
                    table.ajax.reload(() => loadForm(0));
                    return;
                });
        });

        // ✅ Auto-calculate when received value changes (discount is readonly, item discounts already in subtotals)
        $(document).on('input', '#diterima', function () {
            if ($(this).val() == "") {
                $(this).val(0).select();
            }
            loadForm(0, $('#diterima').val());
        });

        // ✅ Submit transaction
        $('.btn-simpan').on('click', function (e) {
            var diterima = parseFloat($('#diterima').val());

            if (isNaN(diterima) || diterima <= 0) {
                e.preventDefault();
                alert('Please enter the amount received before checking out.');
                $('#diterima').focus().select();
                return;
            }

            $('.form-penjualan').submit();
        });

        // ✅ Detect Enter key from barcode scanner to auto-add product
        $('#kode_produk').on('keypress', function (e) {
            if (e.which === 13) { // 13 = Enter key
                e.preventDefault();
                tambahProduk(); // call add function automatically
            }
        });
    });

    // ✅ Show product selection modal (optional button)
    function tampilProduk() {
        $('#modal-produk').modal('show');
    }

    // ✅ Hide product modal
    function hideProduk() {
        $('#modal-produk').modal('hide');
    }

    // ✅ Select product manually from modal
    function pilihProduk(id, kode) {
        $('#id_produk').val(id);
        $('#kode_produk').val(kode);
        hideProduk();
        tambahProduk();
    }

    // ✅ ADD PRODUCT FUNCTION — works for both scanner + manual entry
    function tambahProduk() {
        let kode_produk = $('#kode_produk').val();
        let id_penjualan = $('#id_penjualan').val();

        // Prevent empty submission
        if (kode_produk.trim() === '') return;

        $.post('{{ route('transaksi.store') }}', {
            _token: $('[name=csrf-token]').attr('content'),
            id_penjualan: id_penjualan,
            kode_produk: kode_produk
        })
        .done(function (response) {
            // ✅ Play success beep
            const beep = new Audio('/sounds/beep.mp3');
            beep.play();

            // ✅ Flash input green (successful scan)
            $('#kode_produk').addClass('bg-success text-white');
            setTimeout(() => {
                $('#kode_produk').removeClass('bg-success text-white');
            }, 300);

            // ✅ Reload table and recalc totals
            table.ajax.reload(() => loadForm(0));

            // ✅ Clear input & refocus ready for next scan
            $('#kode_produk').val('').focus();
        })
        .fail(function (xhr) {
            // ❌ Error beep (double sound)
            const beep = new Audio('/sounds/beep.mp3');
            beep.play();
            setTimeout(() => beep.play(), 200);

            // ❌ Flash red to indicate product not found or sold out
            $('#kode_produk').addClass('bg-danger text-white');
            setTimeout(() => {
                $('#kode_produk').removeClass('bg-danger text-white');
            }, 500);

            let errorMessage = '⚠️ Product not found or invalid barcode!';
            if (xhr.responseJSON) {
                if (typeof xhr.responseJSON === 'string') {
                    errorMessage = '⚠️ ' + xhr.responseJSON;
                } else if (xhr.responseJSON.message) {
                    errorMessage = '⚠️ ' + xhr.responseJSON.message;
                }
            } else if (xhr.responseText) {
                try {
                    const parsed = JSON.parse(xhr.responseText);
                    errorMessage = '⚠️ ' + (parsed.message || parsed);
                } catch (e) {
                    errorMessage = '⚠️ ' + xhr.responseText;
                }
            }
            
            alert(errorMessage);
            $('#kode_produk').val('').focus();
        });
    }

    // ✅ Member modal functions
    function tampilMember() {
        $('#modal-member').modal('show');
    }

    function pilihMember(id, kode) {
        $('#id_member').val(id);
        $('#kode_member').val(kode);
        loadForm(0);
        $('#diterima').val(0).focus().select();
        hideMember();
    }

    function hideMember() {
        $('#modal-member').modal('hide');
    }

    // ✅ Delete data row
    function deleteData(url) {
        if (confirm('Are you sure you want to delete selected data?')) {
            $.post(url, {
                    '_token': $('[name=csrf-token]').attr('content'),
                    '_method': 'delete'
                })
                .done((response) => {
                    table.ajax.reload(() => loadForm(0));
                })
                .fail((errors) => {
                    alert('Unable to delete data');
                    return;
                });
        }
    }

    // ✅ Recalculate totals & change (returns formatted currency and words)
    function loadForm(diskon = 0, diterima = 0) {
        $('#total').val($('.total').text());
        $('#total_item').val($('.total_item').text());

        $.get(`{{ url('/transaksi/loadform') }}/${diskon}/${$('.total').text()}/${diterima}`)
            .done(response => {
                $('#totalrp').val('₦ '+ response.totalrp);
                $('#bayarrp').val('₦ '+ response.bayarrp);
                $('#bayar').val(response.bayar);
                $('.tampil-bayar').text('Pay: ₦ '+ response.bayarrp);
                $('.tampil-terbilang').text(response.terbilang);

                $('#kembali').val('₦'+ response.kembalirp);
                if ($('#diterima').val() != 0) {
                    $('.tampil-bayar').text('Return: ₦ '+ response.kembalirp);
                    $('.tampil-terbilang').text(response.kembali_terbilang);
                }
            })
            .fail(errors => {
                alert('Unable to display data');
                return;
            })
    }
</script>
@endpush

{{-- this works too --}}
{{-- @push('scripts')
<script>
    let table, table2;

    $(function () {
        $('body').addClass('sidebar-collapse');

        // === DataTable Initialization ===
        table = $('.table-penjualan').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            autoWidth: false,
            ajax: {
                url: '{{ route('transaksi.data', $id_penjualan) }}',
            },
            columns: [
                {data: 'DT_RowIndex', searchable: false, sortable: false},
                {data: 'kode_produk'},
                {data: 'nama_produk'},
                {data: 'harga_jual'},
                {data: 'jumlah'},
                {data: 'diskon'},
                {data: 'subtotal'},
                {data: 'aksi', searchable: false, sortable: false},
            ],
            dom: 'Brt',
            bSort: false,
            paginate: false
        })
        .on('draw.dt', function () {
            loadForm($('#diskon').val());
            setTimeout(() => {
                $('#diterima').trigger('input');
            }, 300);
        });

        table2 = $('.table-produk').DataTable();

        // === ✅ Barcode Scanner (Enter Key) Handler ===
        $('#kode_produk').on('keypress', function (e) {
            if (e.which === 13) { // Enter key pressed
                e.preventDefault(); // prevent reload
                tambahProduk();     // call function to add product
                $(this).val('').focus(); // clear input and refocus for next scan
            }
        });

        // === Quantity Change ===
        $(document).on('input', '.quantity', function () {
            let id = $(this).data('id');
            let jumlah = parseInt($(this).val());

            if (jumlah < 1) {
                $(this).val(1);
                alert('The number cannot be less than 1');
                return;
            }
            if (jumlah > 10000) {
                $(this).val(10000);
                alert('The number cannot exceed 10000');
                return;
            }

            $.post(`{{ url('/transaksi') }}/${id}`, {
                    '_token': $('[name=csrf-token]').attr('content'),
                    '_method': 'put',
                    'jumlah': jumlah
                })
                .done(response => {
                    $(this).on('mouseout', function () {
                        table.ajax.reload(() => loadForm(0));
                    });
                })
                .fail(errors => {
                    alert('Unable to save data');
                    return;
                });
        });

        // === Discount Input Change ===
        $(document).on('input', '#diskon', function () {
            if ($(this).val() == "") {
                $(this).val(0).select();
            }
            loadForm($(this).val());
        });

        // === Received Amount Input ===
        $('#diterima').on('input', function () {
            if ($(this).val() == "") {
                $(this).val(0).select();
            }
            loadForm($('#diskon').val(), $(this).val());
        }).focus(function () {
            $(this).select();
        });

        // === Save Button Click ===
        $('.btn-simpan').on('click', function () {
            $('.form-penjualan').submit();
        });
    });

    // === Show Product Modal ===
    function tampilProduk() {
        $('#modal-produk').modal('show');
    }

    function hideProduk() {
        $('#modal-produk').modal('hide');
    }

    // === Select Product from Modal ===
    function pilihProduk(id, kode) {
        $('#id_produk').val(id);
        $('#kode_produk').val(kode);
        hideProduk();
        tambahProduk();
    }

    // === Add Product to Sale ===
    function tambahProduk() {
        $.post('{{ route('transaksi.store') }}', $('.form-produk').serialize())
            .done(response => {
                $('#kode_produk').focus();
                table.ajax.reload(() => loadForm($('#diskon').val()));
            })
            .fail(errors => {
                alert('Unable to save data');
                return;
            });
    }

    // === Member Modal ===
    function tampilMember() {
        $('#modal-member').modal('show');
    }

    function hideMember() {
        $('#modal-member').modal('hide');
    }

    function pilihMember(id, kode) {
        $('#id_member').val(id);
        $('#kode_member').val(kode);
        loadForm(0);
        $('#diterima').val(0).focus().select();
        hideMember();
    }

    // === Delete Product from Table ===
    function deleteData(url) {
        if (confirm('Are you sure you want to delete selected data?')) {
            $.post(url, {
                    '_token': $('[name=csrf-token]').attr('content'),
                    '_method': 'delete'
                })
                .done((response) => {
                    table.ajax.reload(() => loadForm(0));
                })
                .fail((errors) => {
                    alert('Unable to delete data');
                    return;
                });
        }
    }

    // === Update Totals and Display ===
    function loadForm(diskon = 0, diterima = 0) {
        $('#total').val($('.total').text());
        $('#total_item').val($('.total_item').text());

        $.get(`{{ url('/transaksi/loadform') }}/${diskon}/${$('.total').text()}/${diterima}`)
            .done(response => {
                $('#totalrp').val('₦ ' + response.totalrp);
                $('#bayarrp').val('₦ ' + response.bayarrp);
                $('#bayar').val(response.bayar);
                $('.tampil-bayar').text('Pay: ₦ ' + response.bayarrp);
                $('.tampil-terbilang').text(response.terbilang);

                $('#kembali').val('₦' + response.kembalirp);
                if ($('#diterima').val() != 0) {
                    $('.tampil-bayar').text('Return: ₦ ' + response.kembalirp);
                    $('.tampil-terbilang').text(response.kembali_terbilang);
                }
            })
            .fail(errors => {
                alert('Unable to display data');
                return;
            });
    }
</script>
@endpush --}}


{{-- @push('scripts')
<script>
    let table, table2;

    $(function () {
        $('body').addClass('sidebar-collapse');

        table = $('.table-penjualan').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            autoWidth: false,
            ajax: {
                url: '{{ route('transaksi.data', $id_penjualan) }}',
            },
            columns: [
                {data: 'DT_RowIndex', searchable: false, sortable: false},
                {data: 'kode_produk'},
                {data: 'nama_produk'},
                {data: 'harga_jual'},
                {data: 'jumlah'},
                {data: 'diskon'},
                {data: 'subtotal'},
                {data: 'aksi', searchable: false, sortable: false},
            ],
            dom: 'Brt',
            bSort: false,
            paginate: false
        })
        .on('draw.dt', function () {
            loadForm($('#diskon').val());
            setTimeout(() => {
                $('#diterima').trigger('input');
            }, 300);
        });
        table2 = $('.table-produk').DataTable();

        $(document).on('input', '.quantity', function () {
            let id = $(this).data('id');
            let jumlah = parseInt($(this).val());

            if (jumlah < 1) {
                $(this).val(1);
                alert('The number cannot be less than 1');
                return;
            }
            if (jumlah > 10000) {
                $(this).val(10000);
                alert('The number cannot exceed 10000');
                return;
            }

            $.post(`{{ url('/transaksi') }}/${id}`, {
                    '_token': $('[name=csrf-token]').attr('content'),
                    '_method': 'put',
                    'jumlah': jumlah
                })
                .done(response => {
                    $(this).on('mouseout', function () {
                        table.ajax.reload(() => loadForm(0));
                    });
                })
                .fail(errors => {
                    alert('Unable to save data');
                    return;
                });
        });

        $(document).on('input', '#diskon', function () {
            if ($(this).val() == "") {
                $(this).val(0).select();
            }

            loadForm($(this).val());
        });

        $('#diterima').on('input', function () {
            if ($(this).val() == "") {
                $(this).val(0).select();
            }

            loadForm($('#diskon').val(), $(this).val());
        }).focus(function () {
            $(this).select();
        });

        $('.btn-simpan').on('click', function () {
            $('.form-penjualan').submit();
        });
    });

    function tampilProduk() {
        $('#modal-produk').modal('show');
    }

    function hideProduk() {
        $('#modal-produk').modal('hide');
    }

    function pilihProduk(id, kode) {
        $('#id_produk').val(id);
        $('#kode_produk').val(kode);
        hideProduk();
        tambahProduk();
    }

    function tambahProduk() {
        $.post('{{ route('transaksi.store') }}', $('.form-produk').serialize())
            .done(response => {
                $('#kode_produk').focus();
                table.ajax.reload(() => loadForm($('#diskon').val()));
            })
            .fail(errors => {
                alert('Unable to save data');
                return;
            });
    }

    function tampilMember() {
        $('#modal-member').modal('show');
    }

    function pilihMember(id, kode) {
        $('#id_member').val(id);
        $('#kode_member').val(kode);
        loadForm(0);
        $('#diterima').val(0).focus().select();
        hideMember();
    }

    function hideMember() {
        $('#modal-member').modal('hide');
    }

    function deleteData(url) {
        if (confirm('Are you sure you want to delete selected data?')) {
            $.post(url, {
                    '_token': $('[name=csrf-token]').attr('content'),
                    '_method': 'delete'
                })
                .done((response) => {
                    table.ajax.reload(() => loadForm(0));
                })
                .fail((errors) => {
                    alert('Unable to delete data');
                    return;
                });
        }
    }

    function loadForm(diskon = 0, diterima = 0) {
        $('#total').val($('.total').text());
        $('#total_item').val($('.total_item').text());

        $.get(`{{ url('/transaksi/loadform') }}/${diskon}/${$('.total').text()}/${diterima}`)
            .done(response => {
                $('#totalrp').val('₦ '+ response.totalrp);
                $('#bayarrp').val('₦ '+ response.bayarrp);
                $('#bayar').val(response.bayar);
                $('.tampil-bayar').text('Pay: ₦ '+ response.bayarrp);
                $('.tampil-terbilang').text(response.terbilang);

                $('#kembali').val('₦'+ response.kembalirp);
                if ($('#diterima').val() != 0) {
                    $('.tampil-bayar').text('Return: ₦ '+ response.kembalirp);
                    $('.tampil-terbilang').text(response.kembali_terbilang);
                }
            })
            .fail(errors => {
                alert('Unable to display data');
                return;
            })
    }
</script>
@endpush --}}
