<?php

namespace App\Services;

use App\Models\Modifier;
use App\Models\Product;

/**
 * CartValidationService
 *
 * Menghitung ulang harga cart POS dari database — TIDAK PERNAH percaya
 * unit_price/modifier price/subtotal yang dikirim dari state Livewire,
 * karena properti tersebut bisa dimanipulasi lewat request yang di-tamper
 * (properti publik Livewire yang tidak #[Locked] bisa disisipi update
 * sewenang-wenang lewat request mentah, terlepas dari binding di HTML).
 *
 * Quantity tetap dipercaya dari client karena dibatasi validasi stok terpisah,
 * tapi seluruh komponen harga selalu diambil ulang dari Product/Modifier di DB.
 */
class CartValidationService
{
    /**
     * @return array{items: array, subtotal: float}
     *
     * @throws \RuntimeException
     */
    public function validateAndPrice(array $cart): array
    {
        $productIds = array_column($cart, 'product_id');
        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $items    = [];
        $subtotal = 0.0;

        foreach ($cart as $cartItem) {
            $product = $products->get($cartItem['product_id']);

            if (!$product || !$product->is_active) {
                throw new \RuntimeException("Produk '" . ($cartItem['product_name'] ?? $cartItem['product_id']) . "' tidak tersedia atau sudah tidak aktif.");
            }

            $qty = max(1, (int) $cartItem['quantity']);

            $modifiers     = [];
            $modifierTotal = 0.0;

            foreach ($cartItem['modifiers'] ?? [] as $modifierId => $modifierData) {
                $modifier = Modifier::find($modifierId);

                if (!$modifier || !$modifier->is_active) {
                    throw new \RuntimeException("Modifier '" . ($modifierData['name'] ?? $modifierId) . "' tidak valid atau tidak aktif.");
                }

                $modQty = max(1, (int) ($modifierData['qty'] ?? 1));
                $modifierTotal += (float) $modifier->price_adjustment * $modQty;

                $modifiers[$modifierId] = [
                    'name'         => $modifier->name,
                    'price'        => (float) $modifier->price_adjustment,
                    'qty'          => $modQty,
                    'component_id' => $modifier->component_id,
                ];
            }

            $unitPrice    = (float) $product->price;
            $itemSubtotal = ($unitPrice + $modifierTotal) * $qty;

            $items[] = [
                'product_id'     => $product->id,
                'product_name'   => $product->name,
                'unit_price'     => $unitPrice,
                'quantity'       => $qty,
                'modifiers'      => $modifiers,
                'modifier_total' => $modifierTotal,
                'subtotal'       => $itemSubtotal,
            ];

            $subtotal += $itemSubtotal;
        }

        return ['items' => $items, 'subtotal' => $subtotal];
    }
}
