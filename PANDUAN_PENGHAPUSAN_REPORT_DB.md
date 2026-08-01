# Panduan Penghapusan Fitur Laporan Multi-Database (Report DB)

Panduan ini berisi daftar file dan baris kode yang harus dihapus/dikembalikan jika Anda ingin menonaktifkan fitur sinkronisasi database laporan pusat (`mysql_report`) dan mengembalikan aplikasi ke sistem satu database bawaan.

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

## 3. Edit File Code Aplikasi

### A. File POS Checkout Component
Path: `*\bakso-malang\app\Livewire\PosCheckout.php`

Hapus baris pemanggilan `ReportSyncService` pada 4 method berikut:

1. **Method `processReturn`** (Sekitar baris 333-334):
   ```php
   // Hapus baris ini:
   app(\App\Services\ReportSyncService::class)->syncReturn($return->load('items'));
   ```

2. **Method `processPayment`** (Sekitar baris 699-700):
   ```php
   // Hapus baris ini:
   app(\App\Services\ReportSyncService::class)->syncTransaction($this->lastTransaction);
   ```

3. **Method `closeShift`** (Sekitar baris 819-820):
   ```php
   // Hapus baris ini:
   app(\App\Services\ReportSyncService::class)->syncShift($shift->load('expenses'));
   ```

4. **Method `closePreviousShift`** (Sekitar baris 883-884):
   ```php
   // Hapus baris ini:
   app(\App\Services\ReportSyncService::class)->syncShift($shift->load('expenses'));
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

*Setelah menghapus semua baris di atas, jalankan perintah `php artisan config:clear` di terminal untuk membersihkan cache konfigurasi.*

