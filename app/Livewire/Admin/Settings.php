<?php

namespace App\Livewire\Admin;

use App\Models\PrinterConfig;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class Settings extends Component
{
    use WithFileUploads;

    // General Settings
    public string $store_name = '';
    public string $store_address = '';
    public string $store_phone = '';
    public float $tax_percentage = 0;
    public string $currency_symbol = 'Rp';
    public string $header_text = '';
    public string $footer_text = '';
    public string $font_family_web = 'Poppins';
    public int $qris_expiry_minutes = 5;

    // Logo Uploads
    public $logo_web;
    public $site_logo;
    public $logo_full;
    public ?string $existing_logo_web = null;
    public ?string $existing_site_logo = null;
    public ?string $existing_logo_full = null;
    public string $logo_type = 'single';

    // Printer Config
    public string $paper_size = '58mm';
    public int $paper_width_px = 200;
    public float $paper_width = 58;
    public string $paper_unit = 'mm';
    public float $margin_top = 0;
    public float $margin_right = 0;
    public float $margin_bottom = 0;
    public float $margin_left = 0;
    public string $font_family = 'monospace';
    public int $font_size_px = 12;
    public bool $auto_print = true;

    // Email Test
    public string $test_email_address = '';

    // Notifikasi Struk (Email/WhatsApp)
    public string $receipt_channel = 'email_only';
    public string $whatsapp_template = '';

    // WhatsApp (Fonnte) — status koneksi & QR pairing
    public bool $fonnteConfigured = false;
    public ?string $waDeviceStatus = null;
    public ?string $waQrImage = null;

    public function mount(): void
    {
        // Load general settings (key without group prefix, group as separate param)
        $this->store_name = Setting::get('store_name', 'Bakso Malang', 'general');
        $this->store_address = Setting::get('store_address', '', 'general');
        $this->store_phone = Setting::get('store_phone', '', 'general');
        $this->tax_percentage = (float) Setting::get('tax_percentage', 0, 'general');
        $this->currency_symbol = Setting::get('currency_symbol', 'Rp', 'general');
        $this->font_family_web = Setting::get('font_family_web', 'Poppins', 'general');
        $this->qris_expiry_minutes = (int) Setting::get('qris_expiry_minutes', 5, 'payment');
        $this->header_text = Setting::get('header_text', '', 'receipt');
        $this->footer_text = Setting::get('footer_text', '', 'receipt');

        $this->existing_logo_web = Setting::get('logo_web', null, 'general');
        $this->existing_site_logo = Setting::get('site_logo', null, 'general');
        $this->existing_logo_full = Setting::get('logo_full', null, 'general');
        $this->logo_type = Setting::get('logo_type', 'single', 'general');

        // Load printer config
        $printer = PrinterConfig::getDefault();
        if ($printer) {
            $this->paper_size = $printer->paper_size;
            $this->paper_width_px = $printer->paper_width_px ?? 200;
            $this->paper_width = (float) $printer->paper_width;
            $this->paper_unit = $printer->paper_unit;
            $this->margin_top = (float) $printer->margin_top;
            $this->margin_right = (float) $printer->margin_right;
            $this->margin_bottom = (float) $printer->margin_bottom;
            $this->margin_left = (float) $printer->margin_left;
            $this->font_family = $printer->font_family;
            $this->font_size_px = $printer->font_size_px;
            $this->auto_print = $printer->auto_print;
        }

        $this->receipt_channel = Setting::get('receipt_channel', 'email_only', 'notification');
        $this->whatsapp_template = Setting::get('whatsapp_template', $this->defaultWhatsappTemplate(), 'notification');

        $this->fonnteConfigured = filled(config('services.fonnte.token'));
        if ($this->fonnteConfigured) {
            $this->checkWhatsappStatus();
        }
    }

    protected function defaultWhatsappTemplate(): string
    {
        return "Halo {nama}, terima kasih sudah berbelanja di {toko}!\n\nStruk pesanan Anda (#{invoice}):\n{link}";
    }

    /**
     * Cek status koneksi device Fonnte. Di-cache singkat supaya tidak
     * memanggil API Fonnte di setiap render halaman Pengaturan.
     */
    public function checkWhatsappStatus(): void
    {
        if (!$this->fonnteConfigured) {
            $this->waDeviceStatus = null;
            return;
        }

        $status = \Illuminate\Support\Facades\Cache::remember('fonnte_device_status', 30, function () {
            return app(\App\Services\FonnteService::class)->getDeviceStatus();
        });

        $this->waDeviceStatus = $status['device_status'] ?? 'disconnect';
    }

    /**
     * Minta QR baru dari Fonnte untuk dipindai lewat WhatsApp di HP.
     */
    public function connectWhatsapp(): void
    {
        if (!$this->fonnteConfigured) {
            $this->dispatch('notify', type: 'error', message: 'Token Fonnte belum diset di server (.env)');
            return;
        }

        $result = app(\App\Services\FonnteService::class)->getQrCode();

        if (!($result['status'] ?? false)) {
            $this->waQrImage = null;
            $reason = $result['reason'] ?? 'Gagal mengambil QR';
            if (str_contains(strtolower($reason), 'already connect')) {
                \Illuminate\Support\Facades\Cache::forget('fonnte_device_status');
                $this->checkWhatsappStatus();
                $this->dispatch('notify', type: 'success', message: 'WhatsApp sudah terhubung');
            } else {
                $this->dispatch('notify', type: 'error', message: $reason);
            }
            return;
        }

        $this->waQrImage = $result['url'] ?? null;
    }

    public function saveNotification(): void
    {
        $this->validate([
            'receipt_channel' => 'required|in:email_only,whatsapp_only,both,prefer_email,prefer_whatsapp,none',
            'whatsapp_template' => 'required|string|max:1000',
        ]);

        Setting::set('receipt_channel', $this->receipt_channel, 'notification');
        Setting::set('whatsapp_template', $this->whatsapp_template, 'notification');

        $this->dispatch('notify', type: 'success', message: 'Pengaturan notifikasi struk berhasil disimpan');
    }

    /**
     * Isi cepat lebar & satuan dari tombol preset (58mm/80mm). Margin dan
     * satuan tetap bisa diubah manual sesudahnya — preset cuma titik awal.
     */
    public function applyPaperPreset(string $size): void
    {
        $this->paper_size = $size;

        if ($size === '58mm') {
            $this->paper_width = 58;
            $this->paper_unit = 'mm';
        } elseif ($size === '80mm') {
            $this->paper_width = 80;
            $this->paper_unit = 'mm';
        }
        // 'custom' => biarkan paper_width/paper_unit yang sedang diisi apa adanya.
    }

    protected function processLogo($image, $type = 'logo_web'): ?string
    {
        if (!$image) return null;

        $extension = strtolower($image->getClientOriginalExtension() ?: $image->extension());
        
        // If it's an ICO or SVG file, store it directly without Intervention GD processing
        if (in_array($extension, ['ico', 'svg'])) {
            $filename = md5($image->getClientOriginalName() . time()) . '.' . $extension;
            $path = 'logos/' . $filename;
            \Illuminate\Support\Facades\Storage::disk('public')->putFileAs('logos', $image, $filename);
            return $path;
        }

        $filename = md5($image->getClientOriginalName() . time()) . '.webp';
        $path = 'logos/' . $filename;

        try {
            // Resize and encode using GD Driver
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            $realPath = $image->getRealPath() ?: $image->getPathname();
            $img = $manager->read($realPath);
            
            if ($type === 'site_logo') {
                $img->scaleDown(width: 128); // favicon standard size limit
            } elseif ($type === 'logo_full') {
                $img->scaleDown(height: 80); // banner lebar, jaga rasio asli
            } else {
                $img->scaleDown(width: 500); // store web logo size
            }
            
            // Save to public storage disk
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, (string) $img->toWebp(quality: 80));

            return $path;
        } catch (\Throwable $e) {
            // Fallback: If GD / Intervention fails for any reason, store original file safely
            $filename = md5($image->getClientOriginalName() . time()) . '.' . ($extension ?: 'png');
            $path = 'logos/' . $filename;
            \Illuminate\Support\Facades\Storage::disk('public')->putFileAs('logos', $image, $filename);
            return $path;
        }
    }

    protected function deleteOldLogo(?string $logoPath): void
    {
        if ($logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($logoPath);
        }
    }

    public function removeLogoWeb(): void
    {
        if ($this->existing_logo_web) {
            $this->deleteOldLogo($this->existing_logo_web);
            Setting::set('logo_web', '', 'general');
            $this->existing_logo_web = null;

            $this->dispatch('settings-updated',
                store_name: $this->store_name,
                logo_web: null,
                site_logo: $this->existing_site_logo ? asset('storage/' . $this->existing_site_logo) : null,
                logo_full: $this->existing_logo_full ? asset('storage/' . $this->existing_logo_full) : null,
                logo_type: $this->logo_type,
                font_family_web: $this->font_family_web
            );

            $this->dispatch('notify', type: 'success', message: 'Logo web berhasil dihapus');
        }
    }

    public function removeSiteLogo(): void
    {
        if ($this->existing_site_logo) {
            $this->deleteOldLogo($this->existing_site_logo);
            Setting::set('site_logo', '', 'general');
            $this->existing_site_logo = null;

            $this->dispatch('settings-updated',
                store_name: $this->store_name,
                logo_web: $this->existing_logo_web ? asset('storage/' . $this->existing_logo_web) : null,
                site_logo: null,
                logo_full: $this->existing_logo_full ? asset('storage/' . $this->existing_logo_full) : null,
                logo_type: $this->logo_type,
                font_family_web: $this->font_family_web
            );

            $this->dispatch('notify', type: 'success', message: 'Site logo (favicon) berhasil dihapus');
        }
    }

    public function removeLogoFull(): void
    {
        if ($this->existing_logo_full) {
            $this->deleteOldLogo($this->existing_logo_full);
            Setting::set('logo_full', '', 'general');
            $this->existing_logo_full = null;

            $this->dispatch('settings-updated',
                store_name: $this->store_name,
                logo_web: $this->existing_logo_web ? asset('storage/' . $this->existing_logo_web) : null,
                site_logo: $this->existing_site_logo ? asset('storage/' . $this->existing_site_logo) : null,
                logo_full: null,
                logo_type: $this->logo_type,
                font_family_web: $this->font_family_web
            );

            $this->dispatch('notify', type: 'success', message: 'Logo panjang berhasil dihapus');
        }
    }

    public function saveGeneral(): void
    {
        $this->validate([
            'logo_web' => 'nullable|file|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'site_logo' => 'nullable|file|mimes:png,jpg,jpeg,svg,webp,ico|max:1024',
            'logo_full' => 'nullable|file|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'logo_type' => 'required|in:single,full',
        ]);

        Setting::set('store_name', $this->store_name, 'general');
        Setting::set('store_address', $this->store_address, 'general');
        Setting::set('store_phone', $this->store_phone, 'general');
        Setting::set('tax_percentage', $this->tax_percentage, 'general', 'float');
        Setting::set('currency_symbol', $this->currency_symbol, 'general');
        Setting::set('font_family_web', $this->font_family_web, 'general');
        Setting::set('qris_expiry_minutes', max(3, min(15, (int)$this->qris_expiry_minutes)), 'payment', 'integer');
        Setting::set('header_text', $this->header_text, 'receipt');
        Setting::set('footer_text', $this->footer_text, 'receipt');
        Setting::set('logo_type', $this->logo_type, 'general');

        // Handle Web Logo upload
        if ($this->logo_web) {
            $newPath = $this->processLogo($this->logo_web, 'logo_web');
            if ($newPath) {
                if ($this->existing_logo_web) {
                    $this->deleteOldLogo($this->existing_logo_web);
                }
                Setting::set('logo_web', $newPath, 'general');
                $this->existing_logo_web = $newPath;
                $this->reset('logo_web');
            }
        }

        // Handle Site Logo (Favicon) upload
        if ($this->site_logo) {
            $newPath = $this->processLogo($this->site_logo, 'site_logo');
            if ($newPath) {
                if ($this->existing_site_logo) {
                    $this->deleteOldLogo($this->existing_site_logo);
                }
                Setting::set('site_logo', $newPath, 'general');
                $this->existing_site_logo = $newPath;
                $this->reset('site_logo');
            }
        }

        // Handle Logo Panjang upload
        if ($this->logo_full) {
            $newPath = $this->processLogo($this->logo_full, 'logo_full');
            if ($newPath) {
                if ($this->existing_logo_full) {
                    $this->deleteOldLogo($this->existing_logo_full);
                }
                Setting::set('logo_full', $newPath, 'general');
                $this->existing_logo_full = $newPath;
                $this->reset('logo_full');
            }
        }

        $this->dispatch('settings-updated',
            store_name: $this->store_name,
            logo_web: $this->existing_logo_web ? asset('storage/' . $this->existing_logo_web) : null,
            site_logo: $this->existing_site_logo ? asset('storage/' . $this->existing_site_logo) : null,
            logo_full: $this->existing_logo_full ? asset('storage/' . $this->existing_logo_full) : null,
            logo_type: $this->logo_type,
            font_family_web: $this->font_family_web
        );

        $this->dispatch('notify', type: 'success', message: 'Pengaturan umum berhasil disimpan');
    }

    public function savePrinter(): void
    {
        $this->validate([
            'paper_size' => 'required|in:58mm,80mm,custom',
            'paper_width' => 'required|numeric|min:1',
            'paper_unit' => 'required|in:px,mm,cm',
            'margin_top' => 'required|numeric|min:0',
            'margin_right' => 'required|numeric|min:0',
            'margin_bottom' => 'required|numeric|min:0',
            'margin_left' => 'required|numeric|min:0',
            'font_family' => 'required|string|max:100',
            'font_size_px' => 'required|integer|min:6|max:72',
        ]);

        $data = [
            'paper_size' => $this->paper_size,
            'paper_width_px' => $this->paper_width_px,
            'paper_width' => $this->paper_width,
            'paper_unit' => $this->paper_unit,
            'margin_top' => $this->margin_top,
            'margin_right' => $this->margin_right,
            'margin_bottom' => $this->margin_bottom,
            'margin_left' => $this->margin_left,
            'font_family' => $this->font_family,
            'font_size_px' => $this->font_size_px,
            'auto_print' => $this->auto_print,
        ];

        $printer = PrinterConfig::getDefault();

        if ($printer) {
            $printer->update($data);
        } else {
            PrinterConfig::create([
                ...$data,
                'name' => 'Default Printer',
                'is_default' => true,
            ]);
        }

        $this->dispatch('notify', type: 'success', message: 'Pengaturan printer berhasil disimpan');
    }

    public function sendTestEmail(): void
    {
        $this->validate([
            'test_email_address' => 'required|email|max:255',
        ]);

        try {
            Mail::raw(
                'Ini adalah email test dari ' . config('app.name') . ".

Jika Anda menerima email ini, konfigurasi Gmail SMTP Anda sudah benar!

Driver  : " . config('mail.default') . "
Host    : " . config('mail.mailers.smtp.host') . "
Port    : " . config('mail.mailers.smtp.port') . "
From    : " . config('mail.from.address'),
                fn ($message) => $message
                    ->to($this->test_email_address)
                    ->subject('[Test] Email SMTP — ' . config('app.name'))
            );

            $this->dispatch('notify', type: 'success', message: 'Email test berhasil dikirim ke ' . $this->test_email_address);
            $this->reset('test_email_address');
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: 'Gagal kirim email: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.settings')
            ->title('Pengaturan');
    }
}
