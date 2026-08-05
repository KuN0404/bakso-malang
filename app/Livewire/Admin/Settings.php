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
    public ?string $existing_logo_web = null;
    public ?string $existing_site_logo = null;

    // Printer Config
    public string $paper_size = '58mm';
    public int $paper_width_px = 200;
    public string $font_family = 'monospace';
    public int $font_size_px = 12;
    public bool $auto_print = true;

    // Email Test
    public string $test_email_address = '';

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

        // Load printer config
        $printer = PrinterConfig::getDefault();
        if ($printer) {
            $this->paper_size = $printer->paper_size;
            $this->paper_width_px = $printer->paper_width_px ?? 200;
            $this->font_family = $printer->font_family;
            $this->font_size_px = $printer->font_size_px;
            $this->auto_print = $printer->auto_print;
        }
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
                font_family_web: $this->font_family_web
            );
            
            $this->dispatch('notify', type: 'success', message: 'Site logo (favicon) berhasil dihapus');
        }
    }

    public function saveGeneral(): void
    {
        $this->validate([
            'logo_web' => 'nullable|file|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'site_logo' => 'nullable|file|mimes:png,jpg,jpeg,svg,webp,ico|max:1024',
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

        $this->dispatch('settings-updated',
            store_name: $this->store_name,
            logo_web: $this->existing_logo_web ? asset('storage/' . $this->existing_logo_web) : null,
            site_logo: $this->existing_site_logo ? asset('storage/' . $this->existing_site_logo) : null,
            font_family_web: $this->font_family_web
        );

        $this->dispatch('notify', type: 'success', message: 'Pengaturan umum berhasil disimpan');
    }

    public function savePrinter(): void
    {
        $printer = PrinterConfig::getDefault();
        
        if ($printer) {
            $printer->update([
                'paper_size' => $this->paper_size,
                'paper_width_px' => $this->paper_width_px,
                'font_family' => $this->font_family,
                'font_size_px' => $this->font_size_px,
                'auto_print' => $this->auto_print,
            ]);
        } else {
            PrinterConfig::create([
                'name' => 'Default Printer',
                'paper_size' => $this->paper_size,
                'paper_width_px' => $this->paper_width_px,
                'font_family' => $this->font_family,
                'font_size_px' => $this->font_size_px,
                'auto_print' => $this->auto_print,
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
