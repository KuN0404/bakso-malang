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
        // Terpicu saat data dibuat (insert) atau diperbarui (update)
        static::saved(function ($model) {
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
                Log::error("[AutoSync] Gagal menyalin saved event model " . get_class($model) . " ID " . $model->getKey() . ": " . $e->getMessage());
            }
        });

        // Terpicu saat data dihapus (delete)
        static::deleted(function ($model) {
            try {
                $connection = 'mysql_report';
                $table = $model->getTable();
                $keyName = $model->getKeyName();
                $keyValue = $model->getKey();

                // Cek apakah model menggunakan Soft Deletes dan sedang melakukan soft delete
                if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                    // Soft delete ditangani oleh event 'saved' karena memperbarui kolom deleted_at
                    return;
                }

                DB::connection($connection)->table($table)->where($keyName, $keyValue)->delete();
            } catch (\Throwable $e) {
                Log::error("[AutoSync] Gagal menyalin deleted event model " . get_class($model) . " ID " . $model->getKey() . ": " . $e->getMessage());
            }
        });
    }
}
