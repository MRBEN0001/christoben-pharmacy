<div class="modal fade" id="modal-produk" tabindex="-1" role="dialog" aria-labelledby="modal-produk">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Select Product</h4>
            </div>
            <div class="modal-body">
                <table class="table table-striped table-bordered table-produk table-hover">
                    <thead>
                        <th width="5%">#</th>
                        <th>Code</th>
                        <th>Barcode</th>
                        <th>Name</th>
                        <th>Purchase Price</th>
                        <th><i class="fa fa-cog"></i></th>
                    </thead>
                    <tbody>
                        @foreach ($produk as $key => $item)
                            <tr class="{{ $item->stok <= 0 ? 'danger' : '' }}">
                                <td width="5%">{{ $key+1 }}</td>
                                <td><span class="label label-success">{{ $item->kode_produk }}</span></td>
                                <td>
                                    @if($item->barcode)
                                        <span class="label label-info">{{ $item->barcode }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $item->nama_produk }}</td>
                                <td>{{ $item->harga_beli }}</td>
                                <td>
                                    @if($item->stok <= 0)
                                        <span class="label label-danger">SOLD OUT</span>
                                    @else
                                        <a href="#" class="btn btn-primary btn-xs btn-flat"
                                            onclick="pilihProduk('{{ $item->id_produk }}', '{{ $item->kode_produk }}')">
                                            <i class="fa fa-check-circle"></i>
                                            Select
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>