<?php

// use DashboardControllers\{
//     DashboardController,
//     KategoriController,
//     LaporanController,
//     ProdukController,
//     MemberController,
//     PengeluaranController,
//     PembelianController,
//     PembelianDetailController,
//     PenjualanController,
//     PenjualanDetailController,
//     SettingController,
//     SupplierController,
//     UserController,
// };
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\PembelianDetailController;
use App\Http\Controllers\PenjualanDetailController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::group(['middleware' => 'auth'], function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ================== Product (for all authenticated users: admin + cashiers) ==================
    Route::get('/produk', [ProdukController::class, 'index'])->name('produk.index');
    Route::get('/produk/data', [ProdukController::class, 'data'])->name('produk.data');
    Route::get('/produk/take-stock', [ProdukController::class, 'takeStockPDF'])->name('produk.take_stock');
    Route::post('/produk/{id}/add-stock', [ProdukController::class, 'addStock'])->name('produk.add_stock');
    Route::post('/produk/add-product', [ProdukController::class, 'store'])->name('produk.add_product');

    // ================== Sales List (for all authenticated users: admin + cashiers) ==================
    Route::group(['middleware' => 'level:1,2'], function () {
        Route::get('/penjualan', [PenjualanController::class, 'index'])->name('penjualan.index');
        Route::get('/penjualan/data', [PenjualanController::class, 'data'])->name('penjualan.data');
        Route::get('/penjualan/daily-sales', [PenjualanController::class, 'dailySales'])->name('penjualan.daily_sales');
        Route::get('/penjualan/daily-sales/pdf', [PenjualanController::class, 'dailySalesPDF'])->name('penjualan.daily_sales_pdf');
        Route::get('/penjualan/daily-room-sales', [PenjualanController::class, 'dailyRoomSales'])->name('penjualan.daily_room_sales');
        Route::get('/penjualan/daily-room-sales/pdf', [PenjualanController::class, 'dailyRoomSalesPDF'])->name('penjualan.daily_room_sales_pdf');
        Route::get('/penjualan/monthly-report', [PenjualanController::class, 'monthlyReportPDF'])->name('penjualan.monthly_report');
        Route::get('/penjualan/weekly-report', [PenjualanController::class, 'weeklyReportPDF'])->name('penjualan.weekly_report');
        Route::get('/penjualan/{id}/resume', [PenjualanController::class, 'resume'])->name('penjualan.resume');
        Route::get('/penjualan/{id}', [PenjualanController::class, 'show'])->name('penjualan.show');
    });

    Route::group(['middleware' => 'level:1'], function () {
         // ================== Category ==================
        Route::get('/kategori/data', [KategoriController::class, 'data'])->name('kategori.data');
        Route::resource('/kategori', KategoriController::class);

         // ================== Section ==================
        Route::get('/section/data', [SectionController::class, 'data'])->name('section.data');
        Route::resource('/section', SectionController::class);

            // ================== Product (Admin Only Actions) ==================

        Route::post('/produk/delete-selected', [ProdukController::class, 'deleteSelected'])->name('produk.delete_selected');
        Route::post('/produk/cetak-barcode', [ProdukController::class, 'cetakBarcode'])->name('produk.cetak_barcode');
        // Route::resource('/produk', ProdukController::class);

        Route::resource('/products', ProdukController::class)->names([
            'index' => 'produk.admin.index',
            'store' => 'produk.store',
            'show' => 'produk.show',
            'update' => 'produk.update',
            'destroy' => 'produk.destroy',
            'create' => 'produk.create',
            'edit' => 'produk.edit'
        ]);

            // ================== Member (Customer) ==================

        Route::get('/member/data', [MemberController::class, 'data'])->name('member.data');
        Route::post('/member/cetak-member', [MemberController::class, 'cetakMember'])->name('member.cetak_member');
        Route::resource('/member', MemberController::class);

            // ================== Supplier ==================

        Route::get('/supplier/data', [SupplierController::class, 'data'])->name('supplier.data');
        Route::resource('/supplier', SupplierController::class);

            // ================== Expense ==================
        Route::get('/pengeluaran/data', [PengeluaranController::class, 'data'])->name('pengeluaran.data');
        Route::resource('/pengeluaran', PengeluaranController::class);

            // ================== Purchase ==================

        Route::get('/pembelian/data', [PembelianController::class, 'data'])->name('pembelian.data');
        Route::get('/pembelian/{id}/create', [PembelianController::class, 'create'])->name('pembelian.create');
        Route::resource('/pembelian', PembelianController::class)
            ->except('create');


        Route::get('/pembelian_detail/{id}/data', [PembelianDetailController::class, 'data'])->name('pembelian_detail.data');
        Route::get('/pembelian_detail/loadform/{diskon}/{total}', [PembelianDetailController::class, 'loadForm'])->name('pembelian_detail.load_form');
        Route::resource('/pembelian_detail', PembelianDetailController::class)
            ->except('create', 'show', 'edit');


                                // ================== Sales (Admin Only Actions) ==================

        // Delete route is admin-only (view routes are above for both admin and cashiers)
        Route::delete('/penjualan/{id}', [PenjualanController::class, 'destroy'])->name('penjualan.destroy');
    });

    // ================== Sales Transactions ==================

    // Route::group(['middleware' => 'level:1,2'], function () {
    //     Route::get('/transaksi/baru', [PenjualanController::class, 'create'])->name('transaksi.baru');
    //     Route::post('/transaksi/simpan', [PenjualanController::class, 'store'])->name('transaksi.simpan');
    //     Route::get('/transaksi/selesai', [PenjualanController::class, 'selesai'])->name('transaksi.selesai');
    //     Route::get('/transaksi/nota-kecil', [PenjualanController::class, 'notaKecil'])->name('transaksi.nota_kecil');
    //     Route::get('/transaksi/nota-besar', [PenjualanController::class, 'notaBesar'])->name('transaksi.nota_besar');

    //     Route::get('/transaksi/{id}/data', [PenjualanDetailController::class, 'data'])->name('transaksi.data');
    //     Route::get('/transaksi/loadform/{diskon}/{total}/{diterima}', [PenjualanDetailController::class, 'loadForm'])->name('transaksi.load_form');
    //     Route::resource('/transaksi', PenjualanDetailController::class)
    //         ->except('create', 'show', 'edit');
    // });


    Route::group(['middleware' => 'level:1,2'], function () {
    
        // Show new sales transaction form
        Route::get('/transaksi/baru', [PenjualanController::class, 'create'])->name('transaksi.baru');
    
        // Save transaction
        Route::post('/transaksi/simpan', [PenjualanController::class, 'store'])->name('transaksi.simpan');

        // Picker (Pharmacy) sends the current basket to Provisions
        Route::post('/transaksi/kirim', [PenjualanController::class, 'sendToProvisions'])->name('transaksi.kirim');

        // Provisions inbox of forwarded baskets + open one to continue
        Route::get('/transaksi/incoming', [PenjualanController::class, 'incoming'])->name('transaksi.incoming');
        Route::get('/transaksi/{id}/receive', [PenjualanController::class, 'receive'])->name('transaksi.receive');
    
        // Show completed transaction
        Route::get('/transaksi/selesai', [PenjualanController::class, 'selesai'])->name('transaksi.selesai');
    
        // Print small receipt
        Route::get('/transaksi/nota-kecil', [PenjualanController::class, 'notaKecil'])->name('transaksi.nota_kecil');
    
        // Print big receipt
        Route::get('/transaksi/nota-besar', [PenjualanController::class, 'notaBesar'])->name('transaksi.nota_besar');
    
        // Get transaction detail data (used for AJAX datatables)
        Route::get('/transaksi/{id}/data', [PenjualanDetailController::class, 'data'])->name('transaksi.data');
    
        // Load calculation form (applies discount, totals, change, etc.)
        Route::get('/transaksi/loadform/{diskon}/{total}/{diterima}', [PenjualanDetailController::class, 'loadForm'])->name('transaksi.load_form');
    
        // Resource routes for transaksi (index, store, update, destroy)
        Route::resource('/transaksi', PenjualanDetailController::class)
            ->except('create', 'show', 'edit');
    });
    

    Route::group(['middleware' => 'level:1'], function () {
                        // ================== Report ==================

        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/data/{awal}/{akhir}', [LaporanController::class, 'data'])->name('laporan.data');
        Route::get('/laporan/pdf/{awal}/{akhir}', [LaporanController::class, 'exportPDF'])->name('laporan.export_pdf');

        Route::get('/user/data', [UserController::class, 'data'])->name('user.data');
        Route::resource('/user', UserController::class);

        Route::get('/setting', [SettingController::class, 'index'])->name('setting.index');
        Route::get('/setting/first', [SettingController::class, 'show'])->name('setting.show');
        Route::post('/setting', [SettingController::class, 'update'])->name('setting.update');
    });
 
    Route::group(['middleware' => 'level:1,2'], function () {
        Route::get('/profil', [UserController::class, 'profil'])->name('user.profil');
        Route::post('/profil', [UserController::class, 'updateProfil'])->name('user.update_profil');
    });

    Route::get('/produk/barcode/all', [ProdukController::class, 'viewAllBarcode'])->name('produk.barcode.all');

});