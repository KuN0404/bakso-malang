<?php

namespace App\Services;

use App\Models\BlockedPhoneNumber;
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
    public function paginate(string $search = '', int $perPage = 15): LengthAwarePaginator
    {
        $grouped = DB::table(function ($query) {
            $query->select('customer_phone as phone', 'customer_name as name', 'customer_email as email', 'created_at')
                ->from('transactions')
                ->whereNotNull('customer_phone')
                ->where('customer_phone', '!=', '')
                ->unionAll(
                    DB::table('self_orders')
                        ->select('customer_phone as phone', 'customer_name as name', 'customer_email as email', 'created_at')
                        ->whereNotNull('customer_phone')
                        ->where('customer_phone', '!=', '')
                );
        }, 'combined_orders')
            ->select([
                'phone',
                DB::raw('MAX(name) as name'),
                DB::raw('MAX(email) as email'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('MIN(created_at) as first_order_at'),
                DB::raw('MAX(created_at) as last_order_at'),
            ])
            ->groupBy('phone')
            ->when($search !== '', function ($query) use ($search) {
                $query->having(function ($q) use ($search) {
                    $q->where('phone', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('last_order_at');

        return $grouped->paginate($perPage);
    }

    /**
     * Ambil set nomor HP yang sedang diblokir dari daftar nomor yang diberikan
     * (satu query tambahan, bukan per-baris — aman dari N+1).
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
