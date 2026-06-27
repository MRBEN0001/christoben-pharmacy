<?php

namespace App\Support;

use App\Models\PenjualanDetail;
use App\Models\Produk;

class SaleStock
{
    public static function outOfStockMessage(Produk $produk): string
    {
        return 'Out of stock — "' . $produk->nama_produk . '" cannot be sold. Please restock or contact admin.';
    }

    public static function setResumeBaseline(int $idPenjualan, array $baseline, bool $stockAlreadyDeducted): void
    {
        session([
            "resume_stock_baseline.{$idPenjualan}" => $baseline,
            "resume_stock_fulfilled.{$idPenjualan}" => $stockAlreadyDeducted,
        ]);
    }

    public static function resumeBaseline(?int $idPenjualan = null): array
    {
        $idPenjualan = $idPenjualan ?? session('id_penjualan');

        if (! $idPenjualan) {
            return [];
        }

        return session("resume_stock_baseline.{$idPenjualan}", []);
    }

    public static function stockAlreadyDeducted(?int $idPenjualan = null): bool
    {
        $idPenjualan = $idPenjualan ?? session('id_penjualan');

        if (! $idPenjualan) {
            return false;
        }

        return (bool) session("resume_stock_fulfilled.{$idPenjualan}", false);
    }

    public static function isResumeEdit(?int $idPenjualan = null): bool
    {
        $idPenjualan = $idPenjualan ?? session('id_penjualan');

        if (! $idPenjualan) {
            return false;
        }

        return session()->has("resume_stock_baseline.{$idPenjualan}");
    }

    public static function baselineQty(int $idProduk, ?int $idPenjualan = null): int
    {
        return (int) (self::resumeBaseline($idPenjualan)[$idProduk] ?? 0);
    }

    public static function quantityInSale(int $idPenjualan, int $idProduk, ?int $excludeDetailId = null): int
    {
        return (int) PenjualanDetail::query()
            ->where('id_penjualan', $idPenjualan)
            ->where('id_produk', $idProduk)
            ->when($excludeDetailId, function ($query) use ($excludeDetailId) {
                $query->where('id_penjualan_detail', '!=', $excludeDetailId);
            })
            ->sum('jumlah');
    }

    public static function canSetQuantity(Produk $produk, int $idPenjualan, int $newQty, ?int $excludeDetailId = null): bool
    {
        if ($newQty < 1) {
            return false;
        }

        $otherQtyInSale = self::quantityInSale($idPenjualan, $produk->id_produk, $excludeDetailId);
        $totalQty = $otherQtyInSale + $newQty;

        if (self::isResumeEdit($idPenjualan)) {
            return $totalQty <= (self::baselineQty($produk->id_produk, $idPenjualan) + (int) $produk->stok);
        }

        if ((int) $produk->stok <= 0) {
            return false;
        }

        return $totalQty <= (int) $produk->stok;
    }

    public static function extraQtyBeyondBaseline(int $idProduk, int $currentQty, ?int $idPenjualan = null): int
    {
        return max(0, $currentQty - self::baselineQty($idProduk, $idPenjualan));
    }

    public static function clearResumeSession(?int $idPenjualan = null): void
    {
        $idPenjualan = $idPenjualan ?? session('id_penjualan');

        if (! $idPenjualan) {
            return;
        }

        session()->forget("resume_stock_baseline.{$idPenjualan}");
        session()->forget("resume_stock_fulfilled.{$idPenjualan}");
    }
}
