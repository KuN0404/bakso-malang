<?php

namespace App\Services;

use App\Models\BlockedPhoneNumber;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CustomerDirectoryService
 *
 * Tidak ada tabel `customers` terpisah — identitas pelanggan disatukan
 * berdasarkan nomor HP dari gabungan riwayat transaksi POS dan Self Order.
 * Service ini membangun daftar pelanggan unik tersebut dalam query
 * ter-agregasi (bukan N+1 per baris).
 */
class CustomerDirectoryService
{
    /**
     * @param string $source '' (semua), 'manual' (POS), atau 'self_order'
     */
    public function paginate(string $search = '', int $perPage = 15, string $source = ''): LengthAwarePaginator
    {
        // Nomor HP mentah bisa masuk dalam beberapa format berbeda (0xxx/62xxx/+62xxx)
        // tergantung channel — normalisasi di level query supaya nomor fisik yang sama
        // tidak terpecah jadi beberapa baris, dan supaya cocok dengan format yang
        // dipakai blocked_phone_numbers (lihat getBlockedMap()).
        $normalizedPhoneExpr = PhoneNumber::sqlNormalizeExpression('phone');

        $grouped = DB::table(function ($query) {
            // transactions.source sudah ada (enum 'pos'/'self_order') — dipakai
            // langsung, jangan diasumsikan semua baris transactions itu 'pos',
            // karena self order yang sudah dibayar juga tercatat di sini.
            $query->select(
                'customer_phone as phone',
                'customer_name as name',
                'customer_email as email',
                'created_at',
                'source'
            )
                ->from('transactions')
                ->whereNotNull('customer_phone')
                ->where('customer_phone', '!=', '')
                ->unionAll(
                    DB::table('self_orders')
                        ->select(
                            'customer_phone as phone',
                            'customer_name as name',
                            'customer_email as email',
                            'created_at',
                            DB::raw("'self_order' as source")
                        )
                        ->whereNotNull('customer_phone')
                        ->where('customer_phone', '!=', '')
                );
        }, 'combined_orders')
            ->select([
                DB::raw("{$normalizedPhoneExpr} as phone"),
                // NULLIF(name, '') supaya nama kosong dari satu order tidak
                // menutupi nama asli dari order lain milik nomor yang sama.
                DB::raw("MAX(NULLIF(name, '')) as name"),
                DB::raw("MAX(NULLIF(email, '')) as email"),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('MIN(created_at) as first_order_at'),
                DB::raw('MAX(created_at) as last_order_at'),
                // 'manual' = order dicatat lewat POS oleh kasir (transactions.source = 'pos').
                DB::raw("MAX(CASE WHEN source = 'pos' THEN 1 ELSE 0 END) as has_manual"),
                DB::raw("MAX(CASE WHEN source = 'self_order' THEN 1 ELSE 0 END) as has_self_order"),
            ])
            // Group by ekspresi normalisasi langsung (bukan alias 'phone') supaya
            // tidak ambigu dengan kolom 'phone' asli dari derived table combined_orders.
            ->groupBy(DB::raw($normalizedPhoneExpr))
            ->when($search !== '', function ($query) use ($search, $normalizedPhoneExpr) {
                $query->havingRaw(
                    "({$normalizedPhoneExpr}) LIKE ? OR MAX(NULLIF(name, '')) LIKE ?",
                    ["%{$search}%", "%{$search}%"]
                );
            })
            ->when($source === 'manual', fn ($q) => $q->having('has_manual', 1))
            ->when($source === 'self_order', fn ($q) => $q->having('has_self_order', 1))
            ->orderByDesc('last_order_at');

        return $grouped->paginate($perPage);
    }

    /**
     * Ambil set nomor HP yang sedang diblokir dari daftar nomor yang diberikan
     * (satu query tambahan, bukan per-baris — aman dari N+1).
     *
     * $phones di sini sudah dalam format normalisasi (lihat paginate() di atas),
     * jadi cocok langsung dengan blocked_phone_numbers.phone yang juga disimpan
     * ternormalisasi — tidak perlu normalisasi ulang di sini.
     */
    public function getBlockedMap(iterable $phones): Collection
    {
        $phones = collect($phones)->filter()->unique()->values();

        if ($phones->isEmpty()) {
            return collect();
        }

        return BlockedPhoneNumber::whereIn('phone', $phones)
            ->where('is_blocked', true)
            ->pluck('phone')
            ->flip();
    }
}
