<?php

namespace App\Services;

use App\Models\Component;
use App\Models\Modifier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Log;

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
    /** Batas kewajaran qty pengganti pada substitusi manual (jaring pengaman anti-tamper). */
    private const MAX_MANUAL_SUBSTITUTE_QTY = 100;

    /**
     * @return array{items: array, subtotal: float}
     *
     * @throws \RuntimeException
     */
    public function validateAndPrice(array $cart, ?int $userId = null): array
    {
        $productIds = array_column($cart, 'product_id');
        $products   = Product::with('bom.activeSubstitutions')->whereIn('id', $productIds)->get()->keyBy('id');

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
                // Substitusi komponen ikut dibawa — kalau tidak, key ini hilang di
                // sini dan pengurangan stok akan memakai komposisi normal, bukan
                // komposisi yang benar-benar dipilih kasir.
                'substitutions'  => $this->validateSubstitutions($product, $cartItem['substitutions'] ?? [], $userId),
            ];

            $subtotal += $itemSubtotal;
        }

        return ['items' => $items, 'subtotal' => $subtotal];
    }

    /**
     * Validasi ulang peta substitusi dari DB — nilainya TIDAK PERNAH dipercaya
     * dari client, sama seperti harga. Tanpa ini, request yang di-tamper bisa
     * mengganti komponen mahal dengan komponen murah, atau menyuntikkan
     * product_bom_id milik produk lain.
     *
     * @param  array  $raw  [productBomId => ['component_id','quantity','rule_id']]
     * @return array  bentuk ternormalisasi, hanya berisi entri yang sah
     *
     * @throws \RuntimeException
     */
    private function validateSubstitutions(Product $product, array $raw, ?int $userId): array
    {
        if (empty($raw)) {
            return [];
        }

        $bomLines = $product->bom->keyBy('id');
        $clean    = [];

        foreach ($raw as $productBomId => $entry) {
            $line = $bomLines->get((int) $productBomId);

            // Baris BOM harus milik produk ini — cegah injeksi lintas produk.
            if (!$line) {
                throw new \RuntimeException(
                    "Substitusi untuk '{$product->name}' sudah tidak berlaku (komposisi produk berubah). Silakan ulangi pemilihan."
                );
            }

            $ruleId = $entry['rule_id'] ?? null;

            if ($ruleId) {
                // Substitusi berbasis aturan admin: qty & komponen diambil dari DB,
                // bukan dari client.
                $rule = $line->activeSubstitutions->firstWhere('id', (int) $ruleId);

                if (!$rule) {
                    throw new \RuntimeException(
                        "Aturan substitusi untuk '{$product->name}' sudah tidak aktif. Silakan ulangi pemilihan."
                    );
                }

                $clean[$line->id] = [
                    'component_id' => (int) $rule->component_id,
                    'quantity'     => (float) $rule->quantity,
                    'rule_id'      => (int) $rule->id,
                ];

                continue;
            }

            // Substitusi manual oleh kasir — butuh permission khusus.
            if ($userId && !User::find($userId)?->can('apply_manual_substitution')) {
                throw new \RuntimeException('Anda tidak memiliki izin untuk mengganti komponen secara manual.');
            }

            $componentId = (int) ($entry['component_id'] ?? 0);
            $quantity    = (float) ($entry['quantity'] ?? 0);
            $component   = $componentId > 0 ? Component::find($componentId) : null;

            if (!$component || !$component->is_active) {
                throw new \RuntimeException('Komponen pengganti tidak ditemukan atau sudah tidak aktif.');
            }

            if ($quantity <= 0 || $quantity > self::MAX_MANUAL_SUBSTITUTE_QTY) {
                throw new \RuntimeException(
                    "Jumlah komponen pengganti tidak wajar (maksimal " . self::MAX_MANUAL_SUBSTITUTE_QTY . ")."
                );
            }

            Log::info(
                "Substitusi manual: user #{$userId} mengganti komponen '{$line->component?->name}' "
                . "× {$line->quantity} dengan '{$component->name}' × {$quantity} pada produk '{$product->name}'."
            );

            $clean[$line->id] = [
                'component_id' => $component->id,
                'quantity'     => $quantity,
                'rule_id'      => null,
            ];
        }

        return $clean;
    }
}
