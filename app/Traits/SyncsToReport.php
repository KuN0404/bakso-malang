<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SyncsToReport Trait
 *
 * Trait ini otomatis menduplikasi setiap perubahan (insert, update, delete)
 * pada model utama (data master) ke database laporan pusat ('mysql_report').
 *
 * Cukup tambahkan "use SyncsToReport;" di dalam class Model.
 */
trait SyncsToReport
{
    public static function bootSyncsToReport(): void
    {
        // Terpicu saat data dibuat (insert) atau diperbarui (update, termasuk restore()
        // karena restore() memanggil save() secara internal).
        static::saved(function ($model) {
            static::pushAttributesToReport($model);
        });

        // PENTING: soft delete (->delete() pada model yang pakai SoftDeletes) TIDAK memicu
        // event 'saved' — Illuminate\Database\Eloquent\SoftDeletes::runSoftDelete() melakukan
        // raw query update (bypass save()) dan hanya memicu event 'trashed'. Tanpa listener
        // ini, deleted_at tidak akan pernah tersalin ke report saat soft delete.
        if (method_exists(static::class, 'softDeleted')) {
            static::softDeleted(function ($model) {
                static::pushAttributesToReport($model);
            });
        }

        // Terpicu saat data dihapus permanen (force delete), atau delete() pada model yang
        // TIDAK memakai SoftDeletes sama sekali.
        static::deleted(function ($model) {
            try {
                // Soft delete ditangani oleh listener 'trashed' di atas, bukan di sini.
                if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                    return;
                }

                $connection = 'mysql_report';
                $table = $model->getTable();
                $keyName = $model->getKeyName();
                $keyValue = $model->getKey();

                DB::connection($connection)->table($table)->where($keyName, $keyValue)->delete();
            } catch (\Throwable $e) {
                Log::error("[AutoSync] Gagal menyalin deleted event model " . get_class($model) . " ID " . $model->getKey() . ": " . $e->getMessage());
            }
        });
    }

    protected static function pushAttributesToReport($model): void
    {
        try {
            $connection = 'mysql_report';
            $table = $model->getTable();
            $attributes = $model->getAttributes();

            // Pastikan password berupa string mentah jika ada di Model User
            // Laravel Eloquent memformat atribut saat get, tapi getAttributes() mengambil data raw.

            DB::connection($connection)->table($table)->upsert(
                [$attributes],
                [$model->getKeyName()],
                array_keys($attributes)
            );
        } catch (\Throwable $e) {
            Log::error("[AutoSync] Gagal menyalin data model " . get_class($model) . " ID " . $model->getKey() . ": " . $e->getMessage());
        }
    }
}
