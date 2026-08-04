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
        $products = Product::select(['id', 'track_stock', 'stock', 'is_active'])
            ->where('is_active', true)
            ->get();

        $stockMap = $products->mapWithKeys(function ($product) {
            $available = $product->getAvailableStock();
            return [
                $product->id => [
                    'available_stock' => $available,
                    'is_available'    => $available > 0 || !$product->track_stock,
                    'is_unlimited'    => !$product->track_stock,
                ],
            ];
        });

        return response()->json([
            'products'   => $stockMap,
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}
