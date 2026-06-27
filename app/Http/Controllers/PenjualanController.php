<?php

namespace App\Http\Controllers;

use PDF;
use App\Models\Produk;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use App\Models\PenjualanDetail;
use App\Support\SaleStock;
use Illuminate\Support\Facades\DB;

class PenjualanController extends Controller
{
    private function getSectionFromRequest(Request $request): ?int
    {
        return $request->filled('section') ? (int) $request->section : null;
    }

    private function getSectionName(?int $sectionId): ?string
    {
        if (! $sectionId) {
            return null;
        }

        return Section::find($sectionId)?->nama_section;
    }

    private function applySectionFilter($transactions, ?int $sectionId)
    {
        if (! $sectionId) {
            return $transactions;
        }

        return $transactions->map(function ($penjualan) use ($sectionId) {
            $filteredDetails = $penjualan->detail->filter(function ($detail) use ($sectionId) {
                return $detail->produk && (int) $detail->produk->id_section === $sectionId;
            });

            if ($filteredDetails->isEmpty()) {
                return null;
            }

            $penjualan->setRelation('detail', $filteredDetails->values());
            $penjualan->total_item = $filteredDetails->sum('jumlah');
            $penjualan->total_harga = $filteredDetails->sum(function ($detail) {
                return $detail->harga_jual * $detail->jumlah;
            });
            $penjualan->bayar = $filteredDetails->sum('subtotal');

            return $penjualan;
        })->filter()->values();
    }

    private function buildProductSummary($transactions): array
    {
        $productSummary = [];

        foreach ($transactions as $penjualan) {
            foreach ($penjualan->detail as $detail) {
                $productName = $detail->produk->nama_produk ?? 'N/A';

                if (! isset($productSummary[$productName])) {
                    $productSummary[$productName] = [
                        'quantity' => 0,
                        'total' => 0,
                    ];
                }

                $productSummary[$productName]['quantity'] += $detail->jumlah;
                $productSummary[$productName]['total'] += $detail->subtotal;
            }
        }

        ksort($productSummary);

        return $productSummary;
    }

    private function saleTotalItem($penjualan)
    {
        if ((float) $penjualan->bayar <= 0 && $penjualan->detail->count() > 0) {
            return $penjualan->detail->sum('jumlah');
        }

        return $penjualan->total_item;
    }

    private function saleTotalHarga($penjualan)
    {
        if ((float) $penjualan->bayar <= 0 && $penjualan->detail->count() > 0) {
            return $penjualan->detail->sum(function ($detail) {
                return $detail->harga_jual * $detail->jumlah;
            });
        }

        return $penjualan->total_harga;
    }

    private function saleTotalBayar($penjualan)
    {
        if ((float) $penjualan->bayar <= 0 && $penjualan->detail->count() > 0) {
            return $penjualan->detail->sum('subtotal');
        }

        return $penjualan->bayar;
    }

    private function generateReceiptNumber(): string
    {
        do {
            $receiptNumber = 'RCP-' . strtoupper(substr(md5(uniqid((string) rand(), true)), 0, 8)) . '-' . date('Ymd');
        } while (Penjualan::where('receipt_number', $receiptNumber)->exists());

        return $receiptNumber;
    }

    private function ensureReceiptNumber(Penjualan $penjualan): string
    {
        if (! empty($penjualan->receipt_number)) {
            return $penjualan->receipt_number;
        }

        $penjualan->receipt_number = $this->generateReceiptNumber();
        $penjualan->save();

        return $penjualan->receipt_number;
    }

    public function index()
    {
        $previousMonth = date('m', strtotime('first day of last month'));
        $previousYear = date('Y', strtotime('first day of last month'));
        $startDate = date('Y-m-01', strtotime("$previousYear-$previousMonth-01"));
        $endDate = date('Y-m-t', strtotime("$previousYear-$previousMonth-01"));
        $weekStartDate = now()->startOfWeek()->format('Y-m-d');
        $weekEndDate = now()->endOfWeek()->format('Y-m-d');
        $sections = Section::orderBy('nama_section')->pluck('nama_section', 'id_section');

        return view('penjualan.index', compact(
            'startDate',
            'endDate',
            'weekStartDate',
            'weekEndDate',
            'sections'
        ));
    }

    public function data(Request $request)
    {
        $sectionId = $this->getSectionFromRequest($request);

        $penjualan = Penjualan::with(['detail.produk.kategori', 'detail.produk.section', 'user'])
            ->where(function ($query) {
                $query->where('bayar', '>', 0)
                    ->orWhere(function ($pending) {
                        $pending->where('bayar', '<=', 0)->whereHas('detail');
                    });
            })
            ->orderBy('id_penjualan', 'desc')
            ->get();

        $penjualan = $this->applySectionFilter($penjualan, $sectionId);

        return datatables()
            ->of($penjualan)
            ->addIndexColumn()
            ->addColumn('total_item', function ($penjualan) {
                return format_uang($this->saleTotalItem($penjualan));
            })
            ->addColumn('total_harga', function ($penjualan) {
                return '₦ '. format_uang($this->saleTotalHarga($penjualan));
            })
            ->addColumn('bayar', function ($penjualan) {
                return '₦ '. format_uang($this->saleTotalBayar($penjualan));
            })
            ->addColumn('tanggal', function ($penjualan) {
                return tanggal_indonesia($penjualan->created_at, false);
            })
            ->addColumn('section', function ($penjualan) {
                $sectionNames = $penjualan->detail->filter(function ($detail) {
                    return $detail->produk !== null && $detail->produk->section !== null;
                })->map(function ($detail) {
                    return $detail->produk->section->nama_section ?? 'N/A';
                })->filter()->unique()->values();

                if ($sectionNames->isEmpty()) {
                    return '-';
                }

                $sections = $sectionNames->take(3)->implode(', ');
                if ($sectionNames->count() > 3) {
                    $sections .= '... (+' . ($sectionNames->count() - 3) . ' more)';
                }

                return $sections;
            })
            ->addColumn('products', function ($penjualan) {
                $products = $penjualan->detail->filter(function($detail) {
                    return $detail->produk !== null;
                })->map(function($detail) {
                    return $detail->produk->nama_produk ?? 'N/A';
                })->filter()->unique()->take(3)->implode(', ');
                
                if ($penjualan->detail->count() > 3) {
                    $products .= '... (+' . ($penjualan->detail->count() - 3) . ' more)';
                }
                
                return $products ?: '-';
            })
            ->addColumn('category', function ($penjualan) {
                $categories = $penjualan->detail->filter(function($detail) {
                    return $detail->produk !== null && $detail->produk->kategori !== null;
                })->map(function($detail) {
                    return $detail->produk->kategori->nama_kategori ?? 'N/A';
                })->filter()->unique()->take(3)->implode(', ');
                
                if ($penjualan->detail->count() > 3) {
                    $categories .= '... (+' . ($penjualan->detail->count() - 3) . ' more)';
                }
                
                return $categories ?: '-';
            })
            ->addColumn('room_details', function ($penjualan) {
                return $penjualan->room_unique_details ?? '-';
            })
            ->addColumn('phone_number', function ($penjualan) {
                return $penjualan->phone_number ?? '-';
            })
            ->addColumn('receipt_number', function ($penjualan) {
                return $this->ensureReceiptNumber($penjualan);
            })
            ->editColumn('diskon', function ($penjualan) {
                return '₦ '. format_uang($penjualan->diskon);
            })
            ->editColumn('kasir', function ($penjualan) {
                return $penjualan->user->name ?? '';
            })
            ->addColumn('aksi', function ($penjualan) {
                $buttons = '<div class="btn-group">';
                $buttons .= '<button onclick="showDetail(`'. route('penjualan.show', $penjualan->id_penjualan) .'`)" class="btn btn-xs btn-primary btn-flat" title="View"><i class="fa fa-eye"></i></button>';
                $buttons .= '<a href="'. route('penjualan.resume', $penjualan->id_penjualan) .'" class="btn btn-xs btn-success btn-flat" title="Sell"><i class="fa fa-shopping-cart"></i></a>';

                if (auth()->user()->level == 1) {
                    $buttons .= '<button onclick="deleteData(`'. route('penjualan.destroy', $penjualan->id_penjualan) .'`)" class="btn btn-xs btn-danger btn-flat" title="Delete"><i class="fa fa-trash"></i></button>';
                }

                $buttons .= '</div>';
                return $buttons;
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }
    // visit "codeastro" for more projects!
    public function create()
    {
        $penjualan = new Penjualan();
        $penjualan->id_member = null;
        $penjualan->total_item = 0;
        $penjualan->total_harga = 0;
        $penjualan->diskon = 0;
        $penjualan->bayar = 0;
        $penjualan->diterima = 0;
        $penjualan->id_user = auth()->id();
        $penjualan->receipt_number = $this->generateReceiptNumber();
        $penjualan->save();

        session(['id_penjualan' => $penjualan->id_penjualan]);
        session()->forget('last_penjualan_id');
        SaleStock::clearResumeSession();
        return redirect()->route('transaksi.index');
    }

    public function resume($id)
    {
        $penjualan = Penjualan::with('detail')->findOrFail($id);

        if ($penjualan->detail->isEmpty()) {
            return redirect()->route('penjualan.index')
                ->with('error', 'This sale has no products.');
        }

        $baseline = [];
        foreach ($penjualan->detail as $item) {
            if ($item->id_produk) {
                $baseline[$item->id_produk] = ($baseline[$item->id_produk] ?? 0) + $item->jumlah;
            }
        }

        SaleStock::setResumeBaseline(
            $penjualan->id_penjualan,
            $baseline,
            (float) $penjualan->bayar > 0
        );

        session(['id_penjualan' => $penjualan->id_penjualan]);
        session()->forget('last_penjualan_id');

        return redirect()->route('transaksi.index');
    }

    public function store(Request $request)
    {
        $penjualan = Penjualan::findOrFail($request->id_penjualan);
        $saleId = $penjualan->id_penjualan;
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', $saleId)
            ->get();

        $baseline = SaleStock::resumeBaseline($saleId);
        $stockAlreadyDeducted = SaleStock::stockAlreadyDeducted($saleId);
        $isResumeEdit = ! empty($baseline);

        $currentByProduct = [];
        foreach ($detail as $item) {
            if (! $item->produk) {
                return redirect()->route('transaksi.index')
                    ->with('error', 'A product in this sale no longer exists.');
            }

            $currentByProduct[$item->id_produk] = ($currentByProduct[$item->id_produk] ?? 0) + $item->jumlah;
        }

        $productIds = array_unique(array_merge(array_keys($baseline), array_keys($currentByProduct)));

        foreach ($productIds as $idProduk) {
            $currentQty = (int) ($currentByProduct[$idProduk] ?? 0);
            $extraQty = $isResumeEdit
                ? SaleStock::extraQtyBeyondBaseline($idProduk, $currentQty, $saleId)
                : $currentQty;

            if ($extraQty <= 0) {
                continue;
            }

            $produk = Produk::find($idProduk);

            if (! $produk || (int) $produk->stok < $extraQty) {
                return redirect()->route('transaksi.index')
                    ->with('error', SaleStock::outOfStockMessage(
                        $produk ?? new Produk(['nama_produk' => 'Unknown'])
                    ));
            }
        }

        try {
            DB::transaction(function () use ($request, $penjualan, $baseline, $currentByProduct, $productIds, $stockAlreadyDeducted, $isResumeEdit) {
                $penjualan->id_member = $request->id_member;
                $penjualan->room_unique_details = $request->room_unique_details ?? null;
                $penjualan->phone_number = $request->phone_number ?? null;

                if (empty($penjualan->receipt_number)) {
                    $penjualan->receipt_number = $this->generateReceiptNumber();
                }

                $penjualan->total_item = $request->total_item;
                $penjualan->total_harga = $request->total;
                $penjualan->diskon = $request->diskon;
                $penjualan->bayar = $request->bayar;
                $penjualan->diterima = $request->diterima;
                $penjualan->update();

                foreach ($productIds as $idProduk) {
                    $baseQty = (int) ($baseline[$idProduk] ?? 0);
                    $currentQty = (int) ($currentByProduct[$idProduk] ?? 0);

                    if ($stockAlreadyDeducted) {
                        $delta = $currentQty - $baseQty;

                        if ($delta === 0) {
                            continue;
                        }

                        $produk = Produk::where('id_produk', $idProduk)->lockForUpdate()->first();

                        if (! $produk) {
                            throw new \RuntimeException('A product in this sale no longer exists.');
                        }

                        if ($delta > 0) {
                            if ((int) $produk->stok < $delta) {
                                throw new \RuntimeException(SaleStock::outOfStockMessage($produk));
                            }

                            $produk->stok -= $delta;
                        } else {
                            $produk->stok += abs($delta);
                        }

                        $produk->update();

                        continue;
                    }

                    if ($currentQty <= 0) {
                        continue;
                    }

                    $produk = Produk::where('id_produk', $idProduk)->lockForUpdate()->first();

                    if (! $produk) {
                        throw new \RuntimeException('A product in this sale no longer exists.');
                    }

                    if (! $isResumeEdit) {
                        $extraQty = $currentQty;

                        if ($extraQty > 0 && (int) $produk->stok < $extraQty) {
                            throw new \RuntimeException(SaleStock::outOfStockMessage($produk));
                        }
                    }

                    $produk->stok -= $currentQty;
                    $produk->update();
                }
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('transaksi.index')
                ->with('error', $e->getMessage());
        }

        session(['last_penjualan_id' => $penjualan->id_penjualan]);
        session()->forget('id_penjualan');
        SaleStock::clearResumeSession($saleId);

        return redirect()->route('transaksi.selesai');
    }

    public function show(Request $request, $id)
    {
        $sectionId = $this->getSectionFromRequest($request);

        $detail = PenjualanDetail::with('produk.section')
            ->where('id_penjualan', $id)
            ->when($sectionId, function ($query) use ($sectionId) {
                $query->whereHas('produk', function ($productQuery) use ($sectionId) {
                    $productQuery->where('id_section', $sectionId);
                });
            })
            ->get();

        return datatables()
            ->of($detail)
            ->addIndexColumn()
            ->addColumn('kode_produk', function ($detail) {
                return '<span class="label label-success">'. ($detail->produk->kode_produk ?? 'N/A') .'</span>';
            })
            ->addColumn('nama_produk', function ($detail) {
                return $detail->produk->nama_produk ?? 'N/A';
            })
            ->addColumn('harga_jual', function ($detail) {
                return '₦ '. format_uang($detail->harga_jual);
            })
            ->addColumn('jumlah', function ($detail) {
                return format_uang($detail->jumlah);
            })
            ->addColumn('diskon', function ($detail) {
                return '₦ '. format_uang($detail->diskon);
            })
            ->addColumn('subtotal', function ($detail) {
                return '₦ '. format_uang($detail->subtotal);
            })
            ->rawColumns(['kode_produk'])
            ->make(true);
    }
    // visit "codeastro" for more projects!
    public function destroy($id)
    {
        $penjualan = Penjualan::find($id);
        $detail    = PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->get();

        if ((float) $penjualan->bayar > 0) {
            foreach ($detail as $item) {
                $produk = Produk::find($item->id_produk);
                if ($produk) {
                    $produk->stok += $item->jumlah;
                    $produk->update();
                }
            }
        }

        foreach ($detail as $item) {
            $item->delete();
        }

        if (session('id_penjualan') == $penjualan->id_penjualan) {
            session()->forget('id_penjualan');
        }

        $penjualan->delete();

        return response(null, 204);
    }

    public function selesai()
    {
        $setting = Setting::first();

        return view('penjualan.selesai', compact('setting'));
    }

    public function notaKecil()
    {
        $setting = Setting::first();
        $idPenjualan = session('last_penjualan_id') ?? session('id_penjualan');
        $penjualan = Penjualan::find($idPenjualan);
        if (! $penjualan) {
            abort(404);
        }

        $this->ensureReceiptNumber($penjualan);
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', $penjualan->id_penjualan)
            ->get();
        
        return view('penjualan.nota_kecil', compact('setting', 'penjualan', 'detail'));
    }

    public function notaBesar()
    {
        $setting = Setting::first();
        $idPenjualan = session('last_penjualan_id') ?? session('id_penjualan');
        $penjualan = Penjualan::find($idPenjualan);
        if (! $penjualan) {
            abort(404);
        }

        $this->ensureReceiptNumber($penjualan);
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', $penjualan->id_penjualan)
            ->get();

        $pdf = PDF::loadView('penjualan.nota_besar', compact('setting', 'penjualan', 'detail'));
        $pdf->setPaper(0,0,609,440, 'potrait');
        return $pdf->stream('Transaction-'. date('Y-m-d-his') .'.pdf');
    }

    public function monthlyReportPDF(Request $request)
    {
        set_time_limit(300);

        $sectionId = $this->getSectionFromRequest($request);
        $sectionName = $this->getSectionName($sectionId);

        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        if (! $startDate || ! $endDate) {
            $previousMonth = date('m', strtotime('first day of last month'));
            $previousYear = date('Y', strtotime('first day of last month'));
            $startDate = date('Y-m-01', strtotime("$previousYear-$previousMonth-01"));
            $endDate = date('Y-m-t', strtotime("$previousYear-$previousMonth-01"));
        }

        $transactions = Penjualan::select('id_penjualan', 'created_at', 'receipt_number', 'room_unique_details', 'total_item', 'total_harga', 'diskon', 'bayar', 'id_user')
            ->with(['detail:id_penjualan_detail,id_penjualan,id_produk,jumlah,subtotal,harga_jual', 'detail.produk:id_produk,nama_produk,id_section', 'detail.produk.section:id_section,nama_section', 'user:id,name'])
            ->where('total_item', '>', 0)
            ->where('bayar', '>', 0)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderBy('created_at', 'asc')
            ->get();

        $transactions = $this->applySectionFilter($transactions, $sectionId);

        $grandTotal = $transactions->sum('bayar');
        $totalTransactions = $transactions->count();

        $transactionIds = $transactions->pluck('id_penjualan')->toArray();
        $allDetails = PenjualanDetail::select('id_penjualan_detail', 'id_penjualan', 'id_produk', 'jumlah', 'subtotal')
            ->with('produk:id_produk,nama_produk,id_section', 'produk.section:id_section,nama_section')
            ->whereIn('id_penjualan', $transactionIds)
            ->when($sectionId, function ($query) use ($sectionId) {
                $query->whereHas('produk', function ($productQuery) use ($sectionId) {
                    $productQuery->where('id_section', $sectionId);
                });
            })
            ->get();

        $totalItems = $allDetails->sum('jumlah');
        $productSummary = $this->buildProductSummary($transactions);
        $monthName = date('F Y', strtotime($startDate));
        $setting = Setting::first();
        $generatedAt = now()->format('d M Y') . ', ' . now()->format('h:i A');

        $pdf = PDF::loadView('penjualan.monthly_report', compact(
            'transactions',
            'allDetails',
            'productSummary',
            'grandTotal',
            'totalTransactions',
            'totalItems',
            'startDate',
            'endDate',
            'monthName',
            'setting',
            'sectionName',
            'generatedAt'
        ));
        $pdf->setPaper('a4', 'portrait');

        $filename = 'Monthly-Sales-Report-' . date('F-Y', strtotime($startDate));
        if ($sectionName) {
            $filename .= '-' . str_replace(' ', '-', $sectionName);
        }

        return $pdf->download($filename . '.pdf');
    }

    public function weeklyReportPDF(Request $request)
    {
        set_time_limit(300);

        $sectionId = $this->getSectionFromRequest($request);
        $sectionName = $this->getSectionName($sectionId);

        $startDate = $request->input('startDate', now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->input('endDate', now()->endOfWeek()->format('Y-m-d'));

        $transactions = Penjualan::select('id_penjualan', 'created_at', 'receipt_number', 'room_unique_details', 'total_item', 'total_harga', 'diskon', 'bayar', 'id_user')
            ->with(['detail:id_penjualan_detail,id_penjualan,id_produk,jumlah,subtotal,harga_jual', 'detail.produk:id_produk,nama_produk,id_section', 'detail.produk.section:id_section,nama_section', 'user:id,name'])
            ->where('total_item', '>', 0)
            ->where('bayar', '>', 0)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderBy('created_at', 'asc')
            ->get();

        $transactions = $this->applySectionFilter($transactions, $sectionId);

        $grandTotal = $transactions->sum('bayar');
        $totalTransactions = $transactions->count();
        $totalItems = $transactions->sum('total_item');
        $productSummary = $this->buildProductSummary($transactions);
        $weekLabel = tanggal_indonesia($startDate, false) . ' to ' . tanggal_indonesia($endDate, false);
        $setting = Setting::first();
        $generatedAt = now()->format('d M Y') . ', ' . now()->format('h:i A');

        $pdf = PDF::loadView('penjualan.weekly_report', compact(
            'transactions',
            'productSummary',
            'grandTotal',
            'totalTransactions',
            'totalItems',
            'startDate',
            'endDate',
            'weekLabel',
            'setting',
            'sectionName',
            'generatedAt'
        ));
        $pdf->setPaper('a4', 'portrait');

        $filename = 'Weekly-Sales-Report-' . $startDate . '-to-' . $endDate;
        if ($sectionName) {
            $filename .= '-' . str_replace(' ', '-', $sectionName);
        }

        return $pdf->download($filename . '.pdf');
    }

    public function dailySales(Request $request)
    {
        $selectedDate = $request->input('date', date('Y-m-d'));
        $startDateTime = $selectedDate . ' 00:00:00';
        $endDateTime = $selectedDate . ' 23:59:59';
        
        // Get all transactions for the date (same as sales list)
        $allTransactions = Penjualan::with(['detail.produk.kategori', 'user'])
            ->where('total_item', '>', 0)
            ->where('bayar', '>', 0)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Filter: exclude sales where any product category has "room" or "suite" (case insensitive)
        $transactions = $allTransactions->filter(function($penjualan) {
            if ($penjualan->detail && $penjualan->detail->count() > 0) {
                foreach ($penjualan->detail as $detail) {
                    if ($detail->produk && $detail->produk->kategori && $detail->produk->kategori->nama_kategori) {
                        $categoryName = strtolower($detail->produk->kategori->nama_kategori);
                        if (stripos($categoryName, 'room') !== false || stripos($categoryName, 'suite') !== false) {
                            return false;
                        }
                    }
                }
            }
            return true;
        })->values();
        
        $grandTotal = $transactions->sum('bayar');
        
        return view('penjualan.daily_sales', compact('transactions', 'grandTotal', 'selectedDate'));
    }

    public function dailySalesPDF(Request $request)
    {
        $sectionId = $this->getSectionFromRequest($request);
        $sectionName = $this->getSectionName($sectionId);

        $selectedDate = $request->input('date', date('Y-m-d'));
        $startDateTime = $selectedDate . ' 00:00:00';
        $endDateTime = $selectedDate . ' 23:59:59';

        $allTransactions = Penjualan::with(['detail.produk.kategori', 'detail.produk.section', 'user'])
            ->where('total_item', '>', 0)
            ->where('bayar', '>', 0)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->orderBy('created_at', 'asc')
            ->get();

        $transactions = $allTransactions->filter(function ($penjualan) {
            if ($penjualan->detail && $penjualan->detail->count() > 0) {
                foreach ($penjualan->detail as $detail) {
                    if ($detail->produk && $detail->produk->kategori && $detail->produk->kategori->nama_kategori) {
                        $categoryName = strtolower($detail->produk->kategori->nama_kategori);
                        if (stripos($categoryName, 'room') !== false || stripos($categoryName, 'suite') !== false) {
                            return false;
                        }
                    }
                }
            }

            return true;
        })->values();

        $transactions = $this->applySectionFilter($transactions, $sectionId);
        $grandTotal = $transactions->sum('bayar');
        $setting = Setting::first();
        $generatedAt = now()->format('d M Y') . ', ' . now()->format('h:i A');

        $pdf = PDF::loadView('penjualan.daily_sales_pdf', compact('transactions', 'grandTotal', 'selectedDate', 'setting', 'sectionName', 'generatedAt'));
        $pdf->setPaper('a4', 'portrait');

        $filename = 'Daily-Sales-Report-' . date('Y-m-d', strtotime($selectedDate));
        if ($sectionName) {
            $filename .= '-' . str_replace(' ', '-', $sectionName);
        }

        return $pdf->download($filename . '.pdf');
    }

    public function dailyRoomSales(Request $request)
    {
        $selectedDate = $request->input('date', date('Y-m-d'));
        $startDateTime = $selectedDate . ' 00:00:00';
        $endDateTime = $selectedDate . ' 23:59:59';
        
        // Get all transactions for the date (same as sales list)
        $allTransactions = Penjualan::with(['detail.produk.kategori', 'user'])
            ->where('total_item', '>', 0)
            ->where('bayar', '>', 0)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Filter: only include sales where any product category has "room" or "suite" (case insensitive)
        $transactions = $allTransactions->filter(function($penjualan) {
            if ($penjualan->detail && $penjualan->detail->count() > 0) {
                foreach ($penjualan->detail as $detail) {
                    if ($detail->produk && $detail->produk->kategori && $detail->produk->kategori->nama_kategori) {
                        $categoryName = strtolower($detail->produk->kategori->nama_kategori);
                        if (stripos($categoryName, 'room') !== false || stripos($categoryName, 'suite') !== false) {
                            return true;
                        }
                    }
                }
            }
            return false;
        })->values();
        
        $grandTotal = $transactions->sum('bayar');
        
        return view('penjualan.daily_room_sales', compact('transactions', 'grandTotal', 'selectedDate'));
    }

    public function dailyRoomSalesPDF(Request $request)
    {
        $selectedDate = $request->input('date', date('Y-m-d'));
        $startDateTime = $selectedDate . ' 00:00:00';
        $endDateTime = $selectedDate . ' 23:59:59';
        
        // Get all transactions for the date (same as daily room sales)
        $allTransactions = Penjualan::with(['detail.produk.kategori', 'user'])
            ->where('total_item', '>', 0)
            ->where('bayar', '>', 0)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Filter: only include sales where any product category has "room" or "suite" (case insensitive)
        $transactions = $allTransactions->filter(function($penjualan) {
            if ($penjualan->detail && $penjualan->detail->count() > 0) {
                foreach ($penjualan->detail as $detail) {
                    if ($detail->produk && $detail->produk->kategori && $detail->produk->kategori->nama_kategori) {
                        $categoryName = strtolower($detail->produk->kategori->nama_kategori);
                        if (stripos($categoryName, 'room') !== false || stripos($categoryName, 'suite') !== false) {
                            return true;
                        }
                    }
                }
            }
            return false;
        })->values();
        
        $grandTotal = $transactions->sum('bayar');
        $setting = Setting::first();
        
        $pdf = PDF::loadView('penjualan.daily_room_sales_pdf', compact('transactions', 'grandTotal', 'selectedDate', 'setting'));
        $pdf->setPaper('a4', 'portrait');
        
        $filename = 'Daily-Room-Sales-Report-' . date('Y-m-d', strtotime($selectedDate)) . '.pdf';
        return $pdf->download($filename);
    }
}
// visit "codeastro" for more projects!