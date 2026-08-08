<?php

use App\Models\BlockedPhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration DATA (bukan skema): menormalisasi kolom blocked_phone_numbers.phone
 * yang mungkin sudah tersimpan dalam format campuran (0812.../62812.../+62812...)
 * ke format kanonik 62xxxxxxxxxxx (lihat BlockedPhoneNumber::normalizePhone()).
 *
 * Idempotent: baris yang sudah dalam format kanonik tidak berubah (no-op update).
 *
 * Penanganan duplikat: karena kolom 'phone' unique, dua baris lama yang merujuk
 * nomor fisik sama dalam format beda (mis. '0812...' dan '62812...') akan
 * bentrok kalau di-UPDATE begitu saja. Untuk tiap grup duplikat, baris yang
 * dipertahankan adalah: is_blocked=true (kalau ada), lalu paling baru di-update.
 * Baris duplikat lainnya dihapus (riwayatnya sudah tidak berarti dibanding baris
 * yang dipertahankan).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $rows = DB::table('blocked_phone_numbers')->orderBy('id')->get();

            $byNormalized = [];
            foreach ($rows as $row) {
                $normalized = BlockedPhoneNumber::normalizePhone($row->phone);
                $byNormalized[$normalized][] = $row;
            }

            foreach ($byNormalized as $normalized => $group) {
                if (count($group) === 1) {
                    $row = $group[0];
                    if ($row->phone !== $normalized) {
                        DB::table('blocked_phone_numbers')->where('id', $row->id)->update(['phone' => $normalized]);
                    }
                    continue;
                }

                // Lebih dari satu baris lama merujuk nomor fisik yang sama.
                // Pertahankan: is_blocked=true dulu, kalau beberapa juga blocked
                // ambil yang updated_at paling baru.
                usort($group, function ($a, $b) {
                    if ((bool) $a->is_blocked !== (bool) $b->is_blocked) {
                        return $b->is_blocked <=> $a->is_blocked;
                    }
                    return strcmp($b->updated_at, $a->updated_at);
                });

                $keep = array_shift($group);
                DB::table('blocked_phone_numbers')->where('id', $keep->id)->update(['phone' => $normalized]);

                $duplicateIds = array_map(fn ($row) => $row->id, $group);
                DB::table('blocked_phone_numbers')->whereIn('id', $duplicateIds)->delete();
            }
        });
    }

    public function down(): void
    {
        // Data migration searah — normalisasi tidak reversibel (format asli sudah
        // tidak disimpan, dan baris duplikat yang dihapus tidak bisa dikembalikan).
    }
};
