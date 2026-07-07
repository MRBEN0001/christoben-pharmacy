<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Produk;
use App\Models\Setting;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use App\Models\PenjualanDetail;
use App\Support\SaleStock;
use Illuminate\Support\Facades\DB;

class PenjualanDetailController extends Controller
{
    private function lineDiscountAmount($hargaJual, $jumlah, $diskon): float
    {
        $lineTotal = $hargaJual * $jumlah;

        return max(0, min((float) $diskon, $lineTotal));
    }

    private function calculateSubtotal($hargaJual, $jumlah, $diskon): int
    {
        $lineTotal = $hargaJual * $jumlah;
        $discount = $this->lineDiscountAmount($hargaJual, $jumlah, $diskon);

        return (int) ($lineTotal - $discount);
    }

    public function index()
    {
        $user = auth()->user();

        $produk = Produk::orderBy('nama_produk')
            ->when($user->isSectionScoped(), function ($query) use ($user) {
                $query->where('id_section', $user->id_section);
            })
            ->get();

        $member = Member::orderBy('nama')->get();
        $diskon = Setting::first()->diskon ?? 0;
        $isPicker = $user->isPicker();

        // Check whether there are any transactions in progress
        if ($id_penjualan = session('id_penjualan')) {
            $penjualan = Penjualan::with('detail')->find($id_penjualan);

            if (! $penjualan) {
                session()->forget('id_penjualan');
                SaleStock::clearResumeSession();

                if (auth()->user()->level == 1) {
                    return redirect()->route('transaksi.baru');
                }

                return redirect()->route('penjualan.index');
            }

            if ((float) $penjualan->bayar > 0 && ! SaleStock::isResumeEdit($id_penjualan)) {
                session()->forget('id_penjualan');
                SaleStock::clearResumeSession();

                if (auth()->user()->level == 1) {
                    return redirect()->route('transaksi.baru');
                }

                return redirect()->route('penjualan.index');
            }

            $memberSelected = $penjualan->member ?? new Member();

            $transactionDiskon = (int) ($penjualan->diskon ?? 0);
            if ($transactionDiskon === 0) {
                $transactionDiskon = (int) PenjualanDetail::where('id_penjualan', $id_penjualan)->sum('diskon');
            }

            return view('penjualan_detail.index', compact('produk', 'member', 'diskon', 'id_penjualan', 'penjualan', 'memberSelected', 'transactionDiskon', 'isPicker'));
        } else {
            if (auth()->user()->level == 1) {
                return redirect()->route('transaksi.baru');
            }

            return redirect()->route('penjualan.index');
        }
    }

    public function data($id)
    {
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', $id)
            ->get();

        $data = array();
        $total = 0;
        $total_item = 0;
        $total_diskon = 0;

        foreach ($detail as $item) {
            $lineMax = $item->harga_jual * $item->jumlah;
            $row = array();
            $row['kode_produk'] = '<span class="label label-success">'. $item->produk['kode_produk'] .'</span';
            $row['nama_produk'] = $item->produk['nama_produk'];
            $row['harga_jual']  = '₦ '. format_uang($item->harga_jual);
            $row['jumlah']      = '<input type="number" class="form-control input-sm quantity" data-id="'. $item->id_penjualan_detail .'" value="'. $item->jumlah .'">';
            $row['diskon']      = '<input type="number" class="form-control input-sm discount-input" data-id="'. $item->id_penjualan_detail .'" data-max="'. $lineMax .'" value="'. (int) $item->diskon .'" min="0" step="1">';
            $row['subtotal']    = '₦ '. format_uang($item->subtotal);
            $row['aksi']        = '<div class="btn-group">
                                    <button onclick="deleteData(`'. route('transaksi.destroy', $item->id_penjualan_detail) .'`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                                </div>';
            $data[] = $row;

            $total += $this->calculateSubtotal($item->harga_jual, $item->jumlah, $item->diskon);
            $total_item += $item->jumlah;
            $total_diskon += (int) $item->diskon;
        }

        $data[] = [
            'kode_produk' => '
                <div class="total hide">'. $total .'</div>
                <div class="total_item hide">'. $total_item .'</div>
                <div class="total_diskon hide">'. $total_diskon .'</div>',
            'nama_produk' => '',
            'harga_jual'  => '',
            'jumlah'      => '',
            'diskon'      => '',
            'subtotal'    => '',
            'aksi'        => '',
        ];

        return datatables()
            ->of($data)
            ->addIndexColumn()
            ->rawColumns(['aksi', 'kode_produk', 'jumlah', 'diskon'])
            ->make(true);
    }

    public function store(Request $request)
    {
        // $produk = Produk::where('id_produk', $request->id_produk)->first();

        $produk = null;
        $user = auth()->user();

        $baseQuery = Produk::query()->when($user->isSectionScoped(), function ($query) use ($user) {
            $query->where('id_section', $user->id_section);
        });

        if ($request->id_produk) {
            $produk = (clone $baseQuery)->where('id_produk', $request->id_produk)->first();
        } elseif ($request->kode_produk) {
            $produk = (clone $baseQuery)->where(function ($query) use ($request) {
                $query->where('barcode', $request->kode_produk)
                    ->orWhere('kode_produk', $request->kode_produk);
            })->first();
        }

        if (! $produk) {
            return response()->json([
                'message' => 'Product not found in your section. Check the barcode or product code.',
            ], 400);
        }

        $produk = Produk::find($produk->id_produk);

        if (! SaleStock::isResumeEdit($request->id_penjualan) && (int) $produk->stok <= 0) {
            return response()->json([
                'message' => SaleStock::outOfStockMessage($produk),
            ], 400);
        }

        $existing = PenjualanDetail::where('id_penjualan', $request->id_penjualan)
            ->where('id_produk', $produk->id_produk)
            ->first();

        if ($existing) {
            $newQty = $existing->jumlah + 1;

            if (! SaleStock::canSetQuantity($produk, $request->id_penjualan, $newQty, $existing->id_penjualan_detail)) {
                return response()->json([
                    'message' => SaleStock::outOfStockMessage($produk),
                ], 400);
            }

            $existing->jumlah = $newQty;
            $existing->diskon = $this->lineDiscountAmount(
                $existing->harga_jual,
                $existing->jumlah,
                $existing->diskon
            );
            $existing->subtotal = $this->calculateSubtotal(
                $existing->harga_jual,
                $existing->jumlah,
                $existing->diskon
            );
            $existing->update();

            return response()->json('Data saved successfully', 200);
        }

        if (SaleStock::baselineQty($produk->id_produk, $request->id_penjualan) === 0 && (int) $produk->stok <= 0) {
            return response()->json([
                'message' => SaleStock::outOfStockMessage($produk),
            ], 400);
        }

        if (! SaleStock::canSetQuantity($produk, $request->id_penjualan, 1)) {
            return response()->json([
                'message' => SaleStock::outOfStockMessage($produk),
            ], 400);
        }

        $diskonAmount = $produk->diskon > 0
            ? (int) round($produk->harga_jual * $produk->diskon / 100)
            : 0;

        $detail = new PenjualanDetail();
        $detail->id_penjualan = $request->id_penjualan;
        $detail->id_produk = $produk->id_produk;
        $detail->harga_jual = $produk->harga_jual;
        $detail->jumlah = 1;
        $detail->diskon = $diskonAmount;
        $detail->subtotal = $this->calculateSubtotal($produk->harga_jual, 1, $diskonAmount);
        $detail->save();

        return response()->json('Data saved successfully', 200);
    }
    // visit "codeastro" for more projects!
    public function update(Request $request, $id)
    {
        $detail = PenjualanDetail::with('produk')->findOrFail($id);
        $produk = $detail->produk;

        if (! $produk) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        if ($request->has('jumlah')) {
            $jumlah = (int) $request->jumlah;

            if ($jumlah < 1) {
                return response()->json(['message' => 'Quantity cannot be less than 1.'], 400);
            }

            $produk = Produk::find($produk->id_produk);

            if (! SaleStock::canSetQuantity($produk, $detail->id_penjualan, $jumlah, $detail->id_penjualan_detail)) {
                return response()->json([
                    'message' => SaleStock::outOfStockMessage($produk),
                ], 400);
            }

            $detail->jumlah = $jumlah;
        }

        if ($request->has('diskon')) {
            $diskon = floatval($request->diskon);
            if ($diskon < 0) {
                $diskon = 0;
            }
            $detail->diskon = $diskon;
        }

        $detail->diskon = $this->lineDiscountAmount(
            $detail->harga_jual,
            $detail->jumlah,
            $detail->diskon
        );

        $detail->subtotal = $this->calculateSubtotal(
            $detail->harga_jual,
            $detail->jumlah,
            $detail->diskon
        );
        $detail->update();
        
        return response()->json(['message' => 'Data updated successfully'], 200);
    }

    public function destroy($id)
    {
        $detail = PenjualanDetail::find($id);
        $detail->delete();

        return response(null, 204);
    }

    public function loadForm($diskon = 0, $total = 0, $diterima = 0)
    {
        $total = (float) $total;
        $diskon = max(0, min((float) $diskon, $total));
        $bayar   = $total - $diskon;
        $kembali = ($diterima != 0) ? $diterima - $bayar : 0;
        $data    = [
            'totalrp' => format_uang($total),
            'bayar' => $bayar,
            'bayarrp' => format_uang($bayar),
            'terbilang' => ucwords(terbilang($bayar). ' Naira'),
            'kembalirp' => format_uang($kembali),
            'kembali_terbilang' => ucwords(terbilang($kembali). ' Naira'),
        ];

        return response()->json($data);
    }
}
// visit "codeastro" for more projects!