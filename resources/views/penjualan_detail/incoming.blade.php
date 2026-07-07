@extends('layouts.master')

@section('title')
Incoming Baskets
@endsection

@section('breadcrumb')
    @parent
    <li class="active">Incoming Baskets</li>
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
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-inbox"></i> Baskets sent from other sections</h3>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th>From</th>
                            <th>Section</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Sent</th>
                            <th width="15%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($baskets as $key => $basket)
                            @php
                                $itemCount = $basket->detail->sum('jumlah');
                                $total = $basket->detail->sum('subtotal');
                            @endphp
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $basket->user->name ?? '—' }}</td>
                                <td>
                                    @if ($basket->user && $basket->user->section)
                                        <span class="label label-primary">{{ $basket->user->section->nama_section }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $itemCount }}</td>
                                <td>₦ {{ format_uang($total) }}</td>
                                <td>{{ $basket->updated_at ? $basket->updated_at->format('d M Y H:i') : '—' }}</td>
                                <td>
                                    <a href="{{ route('transaksi.receive', $basket->id_penjualan) }}" class="btn btn-success btn-sm btn-flat">
                                        <i class="fa fa-shopping-cart"></i> Open
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No incoming baskets right now.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
