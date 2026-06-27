<?php

namespace App\Http\Controllers;

use PDF;
use App\Models\Produk;
use App\Models\Kategori;
use App\Models\Section;
use App\Models\Setting;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    private function expiredProductsCount(): int
    {
        return Produk::whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now()->toDateString())
            ->count();
    }

    private function outOfStockProductsCount(): int
    {
        return Produk::where('stok', '<=', 0)->count();
    }

    private function soonToExpireProductsCount(): int
    {
        return Produk::whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', now()->toDateString())
            ->whereDate('expiry_date', '<=', now()->addDays(30)->toDateString())
            ->count();
    }

    private function soonOutOfStockProductsCount(): int
    {
        return Produk::where('stok', '>', 0)
            ->where('stok', '<=', 3)
            ->count();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $kategori = Kategori::all()->pluck('nama_kategori', 'id_kategori');
        $sections = Section::orderBy('nama_section')->pluck('nama_section', 'id_section');
        $expiredCount = $this->expiredProductsCount();
        $outOfStockCount = $this->outOfStockProductsCount();
        $soonToExpireCount = $this->soonToExpireProductsCount();
        $soonOutOfStockCount = $this->soonOutOfStockProductsCount();

        return view('produk.index', compact(
            'kategori',
            'sections',
            'expiredCount',
            'outOfStockCount',
            'soonToExpireCount',
            'soonOutOfStockCount'
        ));
    }

    public function data(Request $request)
    {
        $query = Produk::leftJoin('kategori', 'kategori.id_kategori', 'produk.id_kategori')
            ->leftJoin('section', 'section.id_section', 'produk.id_section')
            ->select('produk.*', 'nama_kategori', 'nama_section');

        if ($request->filter_expired === 'expired') {
            $query->whereNotNull('produk.expiry_date')
                ->whereDate('produk.expiry_date', '<', now()->toDateString());
        }

        if ($request->filter_out_of_stock === 'out_of_stock') {
            $query->where('produk.stok', '<=', 0);
        }

        if ($request->filter_soon_to_expire === 'soon_to_expire') {
            $query->whereNotNull('produk.expiry_date')
                ->whereDate('produk.expiry_date', '>=', now()->toDateString())
                ->whereDate('produk.expiry_date', '<=', now()->addDays(30)->toDateString());
        }

        if ($request->filter_soon_out_of_stock === 'soon_out_of_stock') {
            $query->where('produk.stok', '>', 0)
                ->where('produk.stok', '<=', 3);
        }

        if ($request->filled('filter_category')) {
            $query->where('produk.id_kategori', $request->filter_category);
        }

        if ($request->filled('filter_section')) {
            $query->where('produk.id_section', $request->filter_section);
        }

        $produk = $query->get();

        $datatable = datatables()
            ->of($produk)
            ->addIndexColumn();
        
        // Only add select_all and aksi columns for admin
        if (auth()->user()->level == 1) {
            $datatable->addColumn('select_all', function ($produk) {
                return '
                    <input type="checkbox" name="id_produk[]" value="'. $produk->id_produk .'">
                ';
            });
            
            $datatable->addColumn('aksi', function ($produk) {
                return '
                <div class="btn-group">
                    <button type="button" onclick="editForm(`'. route('produk.update', $produk->id_produk) .'`)" class="btn btn-xs btn-primary btn-flat"><i class="fa fa-pencil"></i></button>
                    <button type="button" onclick="deleteData(`'. route('produk.destroy', $produk->id_produk) .'`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                    <button type="button" onclick="showAddStockForm(`'. $produk->id_produk .'`, `'. $produk->nama_produk .'`, `'. $produk->stok .'`)" class="btn btn-xs btn-success btn-flat"><i class="fa fa-plus"></i></button>
                </div>
                ';
            });
        } else {
            // Add action column for non-admin users (add stock button and add product button)
            $datatable->addColumn('aksi', function ($produk) {
                return '
                <div class="btn-group">
                    <button type="button" onclick="showAddStockForm(`'. $produk->id_produk .'`, `'. $produk->nama_produk .'`, `'. $produk->stok .'`)" class="btn btn-xs btn-success btn-flat" title="Add Stock"><i class="fa fa-plus"></i></button>
                </div>
                ';
            });
        }
        
        return $datatable
            ->addColumn('kode_produk', function ($produk) {
                return '<span class="label label-success">'. $produk->kode_produk .'</span>';
            })
            ->addColumn('barcode', function ($produk) {
                if (empty($produk->barcode)) {
                    return '<span class="text-muted">—</span>';
                }

                return '<span class="label label-info">'. e($produk->barcode) .'</span>';
            })
            ->addColumn('harga_beli', function ($produk) {
                return number_format($produk->harga_beli, 0, '.', ',');
            })
            ->addColumn('harga_jual', function ($produk) {
                return number_format($produk->harga_jual, 0, '.', ',');
            })
            ->addColumn('stok', function ($produk) {
                if ($produk->stok <= 0) {
                    return '<span class="label label-danger">SOLD OUT</span>';
                }
                if ($produk->stok <= 3) {
                    return '<span class="label label-warning">'. format_uang($produk->stok) .' (Low)</span>';
                }

                return format_uang($produk->stok);
            })
            ->addColumn('expiry_date', function ($produk) {
                if (empty($produk->expiry_date)) {
                    return '<span class="text-muted">—</span>';
                }

                $expiry = \Carbon\Carbon::parse($produk->expiry_date);
                $date = $expiry->format('d M Y');

                if ($expiry->isPast()) {
                    return '<span class="label label-danger">'. $date .' (Expired)</span>';
                }

                if ($expiry->lte(now()->addDays(30))) {
                    return '<span class="label label-warning">'. $date .' (Soon)</span>';
                }

                return $date;
            })
            ->rawColumns(auth()->user()->level == 1 ? ['aksi', 'kode_produk', 'barcode', 'select_all', 'stok', 'expiry_date'] : ['aksi', 'kode_produk', 'barcode', 'stok', 'expiry_date'])
            ->with([
                'expired_count' => $this->expiredProductsCount(),
                'out_of_stock_count' => $this->outOfStockProductsCount(),
                'soon_to_expire_count' => $this->soonToExpireProductsCount(),
                'soon_out_of_stock_count' => $this->soonOutOfStockProductsCount(),
            ])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_produk' => 'required|string|max:255|unique:produk,nama_produk',
            'barcode' => 'nullable|string|max:255|unique:produk,barcode',
            'id_kategori' => 'required|exists:kategori,id_kategori',
            'id_section' => 'required|exists:section,id_section',
            'harga_jual' => 'required|numeric|min:0',
            'diskon' => 'nullable|numeric|min:0|max:100',
            'stok' => 'required|integer|min:0',
            'merk' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date',
        ]);

        $produk = Produk::latest()->first() ?? new Produk();
        $request['kode_produk'] = 'P'. tambah_nol_didepan((int)$produk->id_produk +1, 6);

        $produk = Produk::create($request->all());

        return response()->json('Data saved successfully', 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $produk = Produk::find($id);

        return response()->json($produk);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_produk' => 'required|string|max:255|unique:produk,nama_produk,' . $id . ',id_produk',
            'barcode' => 'nullable|string|max:255|unique:produk,barcode,' . $id . ',id_produk',
            'id_kategori' => 'required|exists:kategori,id_kategori',
            'id_section' => 'required|exists:section,id_section',
            'harga_jual' => 'required|numeric|min:0',
            'diskon' => 'nullable|numeric|min:0|max:100',
            'stok' => 'required|integer|min:0',
            'merk' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date',
        ]);

        $produk = Produk::find($id);
        $produk->update($request->all());

        return response()->json('Data saved successfully', 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $produk = Produk::find($id);
        $produk->delete();

        return response(null, 204);
    }

    public function deleteSelected(Request $request)
    {
        foreach ($request->id_produk as $id) {
            $produk = Produk::find($id);
            $produk->delete();
        }

        return response(null, 204);
    }
    // visit "codeastro" for more projects!
    public function cetakBarcode(Request $request)
    {
        $dataproduk = array();
        foreach ($request->id_produk as $id) {
            $produk = Produk::find($id);
            $dataproduk[] = $produk;
        }

        $no  = 1;
        $pdf = PDF::loadView('produk.barcode', compact('dataproduk', 'no'));
        $pdf->setPaper('a4', 'potrait');
        return $pdf->stream('product.pdf');
    }

    public function takeStockPDF()
    {
        $products = Produk::leftJoin('kategori', 'kategori.id_kategori', 'produk.id_kategori')
            ->leftJoin('section', 'section.id_section', 'produk.id_section')
            ->select('produk.*', 'nama_kategori', 'nama_section')
            ->orderBy('nama_produk')
            ->get();

        $setting = Setting::first();
        $stockDate = now()->format('Y-m-d');
        $totalStock = $products->sum('stok');

        $pdf = PDF::loadView('produk.take_stock_pdf', compact('products', 'setting', 'stockDate', 'totalStock'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('stock-count-' . $stockDate . '.pdf');
    }

    public function viewAllBarcode()
{
    $dataproduk = Produk::all(); // get all products
    $no = 1;

    return view('produk.all_barcode', compact('dataproduk', 'no'));
}

    public function addStock(Request $request, $id)
    {
        $request->validate([
            'stock_to_add' => 'required|numeric|min:1'
        ]);

        $produk = Produk::findOrFail($id);
        $produk->stok += $request->stock_to_add;
        $produk->update();

        return response()->json([
            'message' => 'Stock added successfully',
            'new_stock' => $produk->stok
        ], 200);
    }

}
