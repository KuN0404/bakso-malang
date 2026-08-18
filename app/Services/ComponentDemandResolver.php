<?php

namespace App\Services;

use App\Models\Component;
use App\Models\Modifier;
use App\Models\Product;
use App\Models\StockReservation;

/**
 * ComponentDemandResolver
 *
 * SATU-SATUNYA sumber kebenaran untuk pertanyaan "berapa komponen yang dibutuhkan"
 * dan "berapa unit produk yang masih bisa dibuat". Tampilan POS, add-to-cart,
 * update qty, dan validasi checkout WAJIB memakai service ini — kalau tidak,
 * ketiganya bisa memberi jawaban berbeda (persis bug yang pernah terjadi:
 * kartu produk menampilkan "Stok 3" tapi checkout menolak "stok tidak cukup").
 *
 * Dua aturan penting yang membedakannya dari perhitungan lama:
 *
 * 1. AGREGASI DULU, BARU BAGI. Kebutuhan dijumlahkan per komponen lebih dulu,
 *    baru dibandingkan dengan stok. Perhitungan lama membandingkan per baris BOM
 *    secara terpisah — aman selama tiap komponen hanya muncul sekali, tapi salah
 *    begitu substitusi mengarahkan dua baris ke komponen yang sama. Contoh nyata:
 *    Bakso Urat butuh 1 Bakso Besar + 3 Bakso Kecil, lalu 3 Bakso Kecil
 *    disubstitusi jadi 2 Bakso Besar → kebutuhan Bakso Besar = 1 + 2 = 3, bukan
 *    dua pengecekan terpisah (1 dan 2) yang dua-duanya lolos walau stok cuma 2.
 *
 * 2. SELURUH KERANJANG, BUKAN PER PRODUK. Dua produk berbeda bisa memperebutkan
 *    komponen yang sama. Pemanggil membangun "keranjang kandidat" (keranjang
 *    setelah perubahan yang diinginkan) lalu memanggil shortfalls().
 *
 * Service ini murni baca-hitung, tidak pernah menulis ke database.
 *
 * Peta substitusi ($subMap) di-key oleh product_bom.id:
 *   [productBomId => ['component_id' => int, 'quantity' => float, 'rule_id' => ?int]]
 */
class ComponentDemandResolver
{
    /**
     * Kebutuhan komponen untuk 1 unit produk, sudah teragregasi per komponen.
     *
     * @return array<int,float> [component_id => qty per 1 unit produk]
     */
    public function demandPerUnit(Product $product, array $subMap = []): array
    {
        $bomLines = $product->relationLoaded('bom')
            ? $product->bom
            : $product->bom()->with('component')->get();

        $demand = [];

        foreach ($bomLines as $line) {
            if ((float) $line->quantity <= 0) {
                continue; // baris rusak tidak berkontribusi apa-apa
            }

            $componentId = (int) $line->component_id;
            $quantity    = (float) $line->quantity;

            if (isset($subMap[$line->id])) {
                $subComponentId = (int) ($subMap[$line->id]['component_id'] ?? 0);
                $subQuantity    = (float) ($subMap[$line->id]['quantity'] ?? 0);

                // Substitusi tidak valid diabaikan (tetap pakai komposisi normal)
                // — validasi keras dilakukan di CartValidationService saat checkout.
                if ($subComponentId > 0 && $subQuantity > 0) {
                    $componentId = $subComponentId;
                    $quantity    = $subQuantity;
                }
            }

            // Inilah agregasinya — HARUS '+=', bukan '='.
            $demand[$componentId] = ($demand[$componentId] ?? 0) + $quantity;
        }

        return $demand;
    }

    /**
     * Kebutuhan komponen untuk satu baris keranjang (BOM × qty + komponen modifier).
     *
     * @return array<int,float>
     */
    public function demandForCartItem(array $item, ?Product $product = null): array
    {
        $quantity = max(0, (int) ($item['quantity'] ?? 0));

        if ($quantity === 0) {
            return [];
        }

        $product ??= Product::with('bom.component')->find($item['product_id'] ?? 0);

        $demand = [];

        if ($product && $product->hasBom()) {
            foreach ($this->demandPerUnit($product, $item['substitutions'] ?? []) as $componentId => $perUnit) {
                $demand[$componentId] = ($demand[$componentId] ?? 0) + ($perUnit * $quantity);
            }
        }

        // Komponen yang dipakai modifier ikut memperebutkan stok yang sama.
        foreach ($item['modifiers'] ?? [] as $modifierId => $modifierData) {
            $componentId = (int) ($modifierData['component_id'] ?? 0);

            if ($componentId === 0) {
                // component_id tidak selalu ikut terkirim dari client — ambil dari DB.
                $componentId = (int) (Modifier::find($modifierId)?->component_id ?? 0);
            }

            if ($componentId > 0) {
                // POS memakai key 'qty', Self Order memakai 'quantity' — dukung keduanya,
                // kalau tidak kebutuhan komponen modifier jatuh ke default 1 dan salah hitung.
                $modQty = (float) ($modifierData['qty'] ?? $modifierData['quantity'] ?? 1) * $quantity;
                $demand[$componentId] = ($demand[$componentId] ?? 0) + $modQty;
            }
        }

        return $demand;
    }

    /**
     * Kebutuhan komponen untuk SELURUH keranjang, teragregasi lintas baris dan
     * lintas produk. Inilah yang menutup celah overselling saat dua produk
     * berbeda memakai komponen yang sama.
     *
     * @param  \Illuminate\Support\Collection|null  $preloadedProducts  Produk yang
     *         sudah di-fetch pemanggil (keyBy id), supaya tidak di-query ulang.
     *         Dipakai PosCheckout::addToCart() yang sudah punya produknya dari
     *         Product::getForPos() sesaat sebelum memanggil ini lewat cartFits().
     * @return array<int,float>
     */
    public function demandForCart(array $cart, ?\Illuminate\Support\Collection $preloadedProducts = null): array
    {
        $productIds = [];
        foreach ($cart as $item) {
            if (!empty($item['product_id'])) {
                $productIds[] = (int) $item['product_id'];
            }
        }
        $productIds = array_unique($productIds);

        $preloaded = $preloadedProducts ?? collect();
        $missingIds = array_diff($productIds, $preloaded->keys()->all());

        $fetched = empty($missingIds)
            ? collect()
            : Product::with('bom.component')->whereIn('id', $missingIds)->get()->keyBy('id');

        $products = $preloaded->merge($fetched);

        $total = [];

        foreach ($cart as $item) {
            $product = $products->get((int) ($item['product_id'] ?? 0));

            foreach ($this->demandForCartItem($item, $product) as $componentId => $qty) {
                $total[$componentId] = ($total[$componentId] ?? 0) + $qty;
            }
        }

        return $total;
    }

    /**
     * Stok tersedia per komponen.
     *
     * @param  bool  $reservationAware  Kurangi reservasi aktif Self Order. Default true
     *                                  supaya POS dan Self Order tidak menjual stok yang sama.
     * @return array<int,float> [component_id => available]
     */
    public function availabilityMap(array $componentIds, bool $reservationAware = true): array
    {
        $componentIds = array_values(array_unique(array_map('intval', $componentIds)));

        if (empty($componentIds)) {
            return [];
        }

        $components = Component::whereIn('id', $componentIds)->get()->keyBy('id');

        // Reservasi diambil sekali dengan satu query GROUP BY. Sebelumnya ini
        // memanggil StockReservation::getTotalActiveForComponent() per komponen —
        // di halaman POS yang merender puluhan kartu produk, itu meledak jadi
        // ratusan query.
        $reserved = [];

        if ($reservationAware) {
            $reserved = StockReservation::query()
                ->where('item_type', 'component')
                ->whereIn('item_id', $componentIds)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->groupBy('item_id')
                ->selectRaw('item_id, SUM(quantity) as total')
                ->pluck('total', 'item_id')
                ->toArray();
        }

        $map = [];

        foreach ($componentIds as $componentId) {
            $component = $components->get($componentId);

            if (!$component) {
                $map[$componentId] = 0.0; // komponen hilang/terhapus → dianggap habis
                continue;
            }

            $available = (float) $component->stock - (float) ($reserved[$componentId] ?? 0);

            $map[$componentId] = max(0.0, $available);
        }

        return $map;
    }

    /**
     * Bagian murni (tanpa query) dari maxProducibleUnits() — dipisah supaya
     * pemanggil yang sudah punya $availability (mis. dihitung sekali untuk
     * seluruh grid POS) tidak perlu memicu query availabilityMap() lagi per
     * produk. Lihat maxProducibleUnitsFromAvailability() dan
     * availabilityMapForProducts().
     *
     * @param  array<int,float>  $demand
     * @param  array<int,float>  $availability
     * @param  array<int,float>  $otherDemand
     */
    private function computeMaxUnits(array $demand, array $availability, array $otherDemand = []): int
    {
        if (empty($demand)) {
            return 0;
        }

        $max = PHP_INT_MAX;

        foreach ($demand as $componentId => $perUnit) {
            $available = ($availability[$componentId] ?? 0.0) - (float) ($otherDemand[$componentId] ?? 0);

            // round() sebelum floor(): pembagian float bisa menghasilkan 2.9999999
            // untuk 3/1 dan diam-diam memangkas satu unit.
            $possible = (int) floor(round($available / $perUnit, 6));

            $max = min($max, $possible);
        }

        return max(0, $max);
    }

    /**
     * Sama seperti maxProducibleUnits(), tapi memakai $availability yang sudah
     * dihitung pemanggil (batch) alih-alih memanggil availabilityMap() sendiri.
     * Tidak melakukan query apapun — demandPerUnit() juga murni selama relasi
     * 'bom' sudah di-eager-load.
     *
     * @param  array<int,float>  $availability  Harus mencakup seluruh component_id
     *         hasil demandPerUnit($product, $subMap) — lihat
     *         ComponentDemandResolver::collectComponentIdsForBom().
     * @param  array<int,float>  $otherDemand
     */
    public function maxProducibleUnitsFromAvailability(
        Product $product,
        array $availability,
        array $subMap = [],
        array $otherDemand = []
    ): int {
        return $this->computeMaxUnits($this->demandPerUnit($product, $subMap), $availability, $otherDemand);
    }

    /**
     * Berapa unit produk yang masih bisa dibuat dari stok komponen saat ini.
     *
     * @param  array  $otherDemand  Kebutuhan komponen yang sudah "dipesan" baris lain
     *                              di keranjang, agar sisa yang dihitung realistis.
     */
    public function maxProducibleUnits(
        Product $product,
        array $subMap = [],
        array $otherDemand = [],
        bool $reservationAware = true
    ): int {
        $demand = $this->demandPerUnit($product, $subMap);

        if (empty($demand)) {
            return 0;
        }

        $availability = $this->availabilityMap(array_keys($demand), $reservationAware);

        return $this->computeMaxUnits($demand, $availability, $otherDemand);
    }

    /**
     * Union component_id dari BOM produk (demandPerUnit) DAN kandidat
     * substitusinya (bom.activeSubstitutions, bila sudah di-eager-load) —
     * murni, tanpa query. Dipakai availabilityMapForProducts() untuk
     * mengumpulkan seluruh id yang perlu di-fetch sekali untuk satu halaman.
     *
     * @return array<int,int>
     */
    public function collectComponentIdsForBom(Product $product): array
    {
        $ids = array_keys($this->demandPerUnit($product));

        $bomLines = $product->relationLoaded('bom') ? $product->bom : collect();

        foreach ($bomLines as $line) {
            if ($line->relationLoaded('activeSubstitutions')) {
                foreach ($line->activeSubstitutions as $rule) {
                    $ids[] = (int) $rule->component_id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Satu availabilityMap() untuk SELURUH produk yang ditampilkan (mis. grid
     * POS), bukan satu per produk. Produk harus sudah eager-load 'bom.component'
     * dan idealnya 'bom.activeSubstitutions' juga (lihat Product::scopeForPosDisplay()),
     * supaya benar-benar tanpa query tambahan di sini selain satu availabilityMap().
     *
     * @param  iterable<Product>  $products
     * @return array<int,float>
     */
    public function availabilityMapForProducts(iterable $products): array
    {
        $ids = [];

        foreach ($products as $product) {
            foreach ($this->collectComponentIdsForBom($product) as $id) {
                $ids[] = $id;
            }
        }

        return $this->availabilityMap(array_values(array_unique($ids)));
    }

    /**
     * Pilih aturan substitusi (greedy, satu per baris BOM yang kurang) memakai
     * $availability yang sudah tersedia — diekstrak dari
     * Product::getBomAvailabilityWithSubstitutions() supaya bisa dipakai ulang
     * untuk batch (grid POS) tanpa query availabilityMap() tambahan per produk.
     * Murni, tanpa query — 'bom.activeSubstitutions' harus sudah di-eager-load.
     *
     * @param  array<int,float>  $availability
     * @return array<int,array{component_id:int,quantity:float,rule_id:?int}>
     */
    public function resolveSubstitutions(Product $product, array $availability): array
    {
        $bomLines = $product->relationLoaded('bom') ? $product->bom : collect();

        $subMap = [];

        foreach ($bomLines as $line) {
            if ((float) $line->quantity <= 0) {
                continue;
            }

            // Baris ini sudah cukup? jangan disubstitusi.
            $availableForLine = $availability[$line->component_id] ?? 0.0;
            if ($availableForLine >= (float) $line->quantity) {
                continue;
            }

            if (!$line->relationLoaded('activeSubstitutions')) {
                continue;
            }

            foreach ($line->activeSubstitutions as $rule) {
                $ruleAvailable = $availability[$rule->component_id] ?? 0.0;

                if ($rule->quantity > 0 && $ruleAvailable >= (float) $rule->quantity) {
                    $subMap[$line->id] = [
                        'component_id' => (int) $rule->component_id,
                        'quantity'     => (float) $rule->quantity,
                        'rule_id'      => (int) $rule->id,
                    ];
                    break;
                }
            }
        }

        return $subMap;
    }

    /**
     * Cek apakah seluruh keranjang muat dengan stok komponen yang ada.
     *
     * @param  \Illuminate\Support\Collection|null  $preloadedProducts  Diteruskan ke
     *         demandForCart() — lihat catatan di sana.
     * @return array<int,array{component:string,component_id:int,needed:float,available:float,unit:?string}>
     *         Kosong berarti aman.
     */
    public function shortfalls(array $cart, bool $reservationAware = true, ?\Illuminate\Support\Collection $preloadedProducts = null): array
    {
        $demand = $this->demandForCart($cart, $preloadedProducts);

        if (empty($demand)) {
            return [];
        }

        $availability = $this->availabilityMap(array_keys($demand), $reservationAware);

        // Komponen yang benar-benar kurang dulu, baru query Component::with('unit')
        // — dan HANYA untuk id yang kurang, bukan seluruh $demand. Kasus normal
        // (keranjang muat) jadi nol query tambahan di sini.
        $shortIds = [];
        foreach ($demand as $componentId => $needed) {
            if ($needed > ($availability[$componentId] ?? 0.0) + 1e-6) {
                $shortIds[] = $componentId;
            }
        }

        if (empty($shortIds)) {
            return [];
        }

        $components = Component::with('unit')->whereIn('id', $shortIds)->get()->keyBy('id');

        $errors = [];

        foreach ($shortIds as $componentId) {
            $component = $components->get($componentId);

            $errors[] = [
                'component_id' => $componentId,
                'component'    => $component?->name ?? "Komponen #{$componentId}",
                'needed'       => round($demand[$componentId], 3),
                'available'    => round($availability[$componentId] ?? 0.0, 3),
                'unit'         => $component?->unit?->symbol,
            ];
        }

        return $errors;
    }
}
