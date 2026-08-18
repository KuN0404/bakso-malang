<?php

namespace App\Http\Controllers\SelfOrder;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockReservation;
use Illuminate\Http\JsonResponse;

/**
 * SelfOrderStockController
 *
 * API untuk mendapatkan stok produk yang tersedia secara real-time.
 * Digunakan oleh halaman Self Order untuk polling stok setiap 5 detik.
 *
 * Endpoint: GET /api/self-order/stock
 * Throttle: 30/menit per IP
 */
class SelfOrderStockController extends Controller
{
    public function __invoke(): JsonResponse
    {
        // Eager load BOM: tanpa ini hasBom() memicu satu query exists() per produk aktif
        // pada setiap poll (N+1). Kolom `stock`/`track_stock` tetap dibutuhkan untuk
        // produk non-BOM.
        $products = Product::select(['id', 'track_stock', 'stock', 'is_active'])
            ->with('bom.component')
            ->where('is_active', true)
            ->get();

        $stockMap = $products->mapWithKeys(function ($product) {
            $available = $product->getAvailableStock();
            $hasBom    = $product->hasBom();

            return [
                $product->id => [
                    'available_stock' => $available,
                    // Produk ber-BOM tidak pernah "unlimited": stoknya dibatasi komponen.
                    // Sebelumnya keduanya hanya melihat track_stock, sehingga produk BOM
                    // dengan track_stock=false selalu dilaporkan tersedia & tanpa batas
                    // walau komponennya nol.
                    'is_available'    => $product->isAvailable(),
                    'is_unlimited'    => !$hasBom && !$product->track_stock,
                ],
            ];
        });

        return response()->json([
            'products'   => $stockMap,
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}
