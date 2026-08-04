# Panduan Penghapusan Fitur Laporan Multi-Database (Report DB)

Panduan ini berisi daftar lengkap file dan baris kode yang harus dihapus/dikembalikan jika Anda ingin menonaktifkan fitur sinkronisasi database laporan pusat (`mysql_report`) dan mengembalikan aplikasi ke sistem satu database bawaan.

---

## 1. File yang Harus Dihapus (Delete)
Silakan hapus file-file baru berikut dari proyek Anda:
* 🗑️ `*\bakso-malang\app\Traits\SyncsToReport.php`
* 🗑️ `*\bakso-malang\app\Services\ReportSyncService.php`
* 🗑️ `*\bakso-malang\app\Console\Commands\CleanOldData.php`

---

## 2. Edit File Konfigurasi

### A. File `.env` & `.env.example`
Path: 
* `*\bakso-malang\.env`
* `*\bakso-malang\.env.example`

Hapus variabel konfigurasi database laporan berikut:
```env
# Database Laporan (mysql_report)
DB_REPORT_CONNECTION=mysql
DB_REPORT_HOST=127.0.0.1
DB_REPORT_PORT=3306
DB_REPORT_DATABASE=...
DB_REPORT_USERNAME=...
DB_REPORT_PASSWORD=...
```

### B. File Database Config
Path: `*\bakso-malang\config\database.php`

Hapus entri koneksi `'mysql_report'` yang berada di dalam array `'connections'`:
```php
// HAPUS BLOK INI:
'mysql_report' => [
    'driver' => 'mysql',
    'url' => env('DB_REPORT_URL'),
    ...
],
```

### C. File Console Route (Scheduler)
Path: `*\bakso-malang\routes\console.php`

Hapus baris penjadwalan pembersihan data otomatis:
```php
// HAPUS BARIS INI:
\Illuminate\Support\Facades\Schedule::command('data:clean')->monthlyOn(1, '02:00');
```

---

## 3. Edit File Code Aplikasi & Livewire

### A. File POS Checkout Component
Path: `*\bakso-malang\app\Livewire\PosCheckout.php`

Hapus baris pemanggilan `ReportSyncService` pada method berikut:

1. **Method `processReturn`**:
   ```php
   // Hapus baris ini:
   app(\App\Services\ReportSyncService::class)->syncReturn($return->load('items'));
   ```

2. **Method `closeShift`**:
   ```php
   // Hapus baris ini:
   app(\App\Services\ReportSyncService::class)->syncShift($shift->load('expenses'));
   ```

3. **Method `closePreviousShift`**:
   ```php
   // Hapus baris ini:
   app(\App\Services\ReportSyncService::class)->syncShift($shift->load('expenses'));
   ```

### B. Action Pembayaran POS (Cash & Midtrans Webhook)
1. Path: `*\bakso-malang\app\Actions\Payment\InitiateCashPaymentAction.php`
   - Hapus import `use App\Services\ReportSyncService;`
   - Hapus dependency injection `$reportSyncService` di constructor.
   - Hapus baris pemanggilan `$this->reportSyncService->syncTransaction($transaction);`.

2. Path: `*\bakso-malang\app\Actions\Payment\HandleMidtransWebhookAction.php`
   - Hapus import `use App\Services\ReportSyncService;`
   - Hapus dependency injection `$reportSyncService` di constructor.
   - Hapus baris pemanggilan `$this->reportSyncService->syncTransaction($transaction);`.

### C. Livewire Modul Inventori, Pembelian & Repacking Produksi
1. Path: `*\bakso-malang\app\Livewire\Admin\Purchases.php`
   ```php
   // Hapus baris ini di method save():
   app(\App\Services\ReportSyncService::class)->syncPurchase($purchase->load('items'));
   ```

2. Path: `*\bakso-malang\app\Livewire\Admin\Productions.php`
   ```php
   // Hapus baris ini di method save():
   app(\App\Services\ReportSyncService::class)->syncProduction($production->load(['inputs', 'outputs']));
   ```

3. Path: `*\bakso-malang\app\Livewire\Admin\Ingredients.php`
   ```php
   // Hapus baris ini di method save() (ada 2 tempat):
   app(\App\Services\ReportSyncService::class)->syncIngredient($ingredient);
   ```

4. Path: `*\bakso-malang\app\Livewire\Admin\Components.php`
   ```php
   // Hapus baris ini di method save() (ada 2 tempat):
   app(\App\Services\ReportSyncService::class)->syncComponent($comp);
   ```

### D. Model Event Listener (Mutasi Stok Log)
1. Path: `*\bakso-malang\app\Models\IngredientStockLog.php`
   Hapus method `booted()`:
   ```php
   protected static function booted(): void
   {
       static::created(function ($log) {
           app(\App\Services\ReportSyncService::class)->syncIngredientStockLog($log);
       });
   }
   ```

2. Path: `*\bakso-malang\app\Models\ComponentStockLog.php`
   Hapus method `booted()`:
   ```php
   protected static function booted(): void
   {
       static::created(function ($log) {
           app(\App\Services\ReportSyncService::class)->syncComponentStockLog($log);
       });
   }
   ```

---

## 4. Hapus Trait pada Model-Model Master

Buka file model berikut, hapus import `use App\Traits\SyncsToReport;` di bagian atas dan deklarasi `use SyncsToReport;` di dalam class modelnya:

1. 📄 `*\bakso-malang\app\Models\User.php`
2. 📄 `*\bakso-malang\app\Models\Category.php`
3. 📄 `*\bakso-malang\app\Models\Product.php`
4. 📄 `*\bakso-malang\app\Models\PaymentSource.php`
5. 📄 `*\bakso-malang\app\Models\ServiceArea.php`
6. 📄 `*\bakso-malang\app\Models\ModifierGroup.php`
7. 📄 `*\bakso-malang\app\Models\Modifier.php`
8. 📄 `*\bakso-malang\app\Models\Component.php`

---

*Setelah menghapus semua baris di atas, jalankan perintah `php artisan config:clear` dan `php artisan route:clear` di terminal untuk membersihkan cache konfigurasi.*
