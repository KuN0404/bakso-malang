# Walkthrough — Integrasi Pembayaran Midtrans QRIS pada Modul POS

Integrasi payment gateway **Midtrans (QRIS)** pada modul POS (`PosCheckout`) telah selesai diimplementasikan secara komprehensif mengikuti standar arsitektur **Laravel 12** dan **Livewire 3**.

---

## 🌟 Fitur Utama yang Ditambahkan

1. **Dua Metode Pembayaran di POS**:
   - **Cash (Tunai)**: Pemrosesan langsung secara atomik, pembuatan transaksi POS, pemotongan stok & log.
   - **QRIS (Midtrans)**: Pembuatan QR Code dinamis via Midtrans Core API dengan pemantauan status *real-time* via **SSE (Server-Sent Events)**.
2. **State Machine & Database Audit Trail**:
   - Status pembayaran dikelola via Enum `PaymentTransactionStatus` (`initiated` ➔ `pending` ➔ `paid`/`expired`/`failed`/`cancelled`).
   - Setiap perubahan status dicatat lengkap di tabel `payment_status_logs` untuk audit.
3. **Keamanan & Integritas Transaksi**:
   - **Verifikasi Signature SHA512**: Middleware `VerifyMidtransSignature` memvalidasi callback webhook dari Midtrans.
   - **Row-Level Locking (`lockForUpdate`)**: Mencegah race condition jika webhook dan SSE memproses transaksi bersamaan.
   - **Idempotency Check**: Mencegah duplikasi pembayaran atau eksekusi ganda jika webhook diterima berulangkali.
   - **Durasi QRIS Dinamis**: Durasi validitas QRIS dikonfigurasi melalui Settings (`qris_expiry_minutes`, default: 5 menit).

---

## 🔄 Penanganan 5 Edge Cases

| Edge Case | Solusi yang Diimplementasikan |
|---|---|
| **Case 1: Batal QRIS & Ganti ke Cash** | Tombol *"Ganti Metode Pembayaran"* memanggil `CancelPaymentTransactionAction` (pembatalan ke Midtrans secara *best-effort* & update DB ke `cancelled`), lalu mengembalikan kasir ke modal Cash tanpa duplikasi data. |
| **Case 2: Cash ganti ke QRIS** | Kasir dapat beralih ke QRIS sebelum checkout. Transaksi QRIS baru dibuat tanpa menyimpan data pembayaran tunai prematur. |
| **Case 3: Pelanggan Bayar tetapi Koneksi Lambat** | SSE otomatis menerima update saat webhook Midtrans settlement tiba. Disediakan juga tombol **"Cek Status Pembayaran"** (manual check) sebagai fallback. |
| **Case 4: Refresh Browser / Koneksi Terputus** | State transaksi tersimpan aman di database `payment_transactions`. SSE dan frontend otomatis melakukan sync ulang status. |
| **Case 5: QRIS Expired** | Countdown timer + Job Scheduler `CheckExpiredQrisPaymentJob` menandai QRIS kadaluarsa. Tombol **"Buat QRIS Baru"** muncul otomatis di modal kasir. |

---

## 🛠️ Berkas yang Dibuat & Diubah

### Model & Enums Baru
- `app/Enums/PaymentTransactionStatus.php` — State machine status pembayaran.
- `app/Enums/PaymentMethod.php` — Enum metode pembayaran.
- `app/Models/PaymentTransaction.php` — Model inti transaksi gateway + method query OOP.
- `app/Models/PaymentStatusLog.php` — Audit trail log status.
- `app/Models/Transaction.php` — Menambahkan relasi & kolom gateway.

### Services & DTOs
- `app/Services/MidtransService.php` — Wrapper SDK Midtrans (CoreAPI charge, status, cancel, signature verification).
- `app/DTOs/Payment/CartPayload.php` — Immutable DTO payload keranjang.
- `app/DTOs/Payment/MidtransWebhookPayload.php` — Immutable DTO callback webhook.

### Actions (Domain Logic)
- `app/Actions/Payment/InitiateCashPaymentAction.php` — Atomic cash payment.
- `app/Actions/Payment/InitiateQrisPaymentAction.php` — Initiates QRIS charge via Midtrans.
- `app/Actions/Payment/HandleMidtransWebhookAction.php` — Idempotent webhook handler dengan `lockForUpdate`.
- `app/Actions/Payment/CancelPaymentTransactionAction.php` — Cancels QRIS transaction.

### HTTP Layer & Middleware
- `app/Http/Middleware/VerifyMidtransSignature.php` — Verifikasi SHA512 signature.
- `app/Http/Controllers/Payment/MidtransWebhookController.php` — Receiver Webhook Midtrans.
- `app/Http/Controllers/Payment/QrisStatusSseController.php` — SSE Endpoint (`/api/payment/qris-status/{orderId}`).

### Background Jobs & Scheduler
- `app/Jobs/Payment/CheckExpiredQrisPaymentJob.php` — Safety net job expire QRIS.
- `routes/console.php` — Pendaftaran scheduler job setiap menit.

### UI & Component Update
- `app/Livewire/PosCheckout.php` — Refaktor `processPayment()`, integrasi QRIS Actions, SSE handling.
- `resources/views/livewire/pos-checkout.blade.php` — Modal QRIS dengan countdown live, SSE listener, manual status check button.

---

## ⚙️ Panduan Konfigurasi NGINX di AAPanel (Penting untuk SSE)

Agar response streaming **SSE (Server-Sent Events)** berjalan lancar tanpa tertahan buffering Nginx di AAPanel, tambahkan aturan berikut pada konfigurasi vhost Nginx domain Anda:

```nginx
# Konfigurasi SSE untuk Endpoint QRIS Status
location /api/payment/qris-status/ {
    proxy_pass http://127.0.0.1:8000; # Sesuaikan dengan upstream PHP/App Anda
    proxy_set_header Connection '';
    proxy_http_version 1.1;
    chunked_transfer_encoding off;
    
    # Matikan FastCGI / Proxy Buffering agar SSE dapat dikirim secara real-time
    fastcgi_buffering off;
    proxy_buffering off;
    fastcgi_read_timeout 600s;
    proxy_read_timeout 600s;
}
```

---

## 🧪 Hasil Pengujian Unit & Feature Test

```bash
php artisan test --filter=QrisPaymentTest
```
- `payment transaction state machine validates transitions` ➔ **PASS**
- `idempotent webhook handling` ➔ **PASS**
- Total: **2 Passed (8 assertions)**
