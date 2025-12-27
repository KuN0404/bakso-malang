<?php

namespace App\Livewire\Admin;

use App\Models\PrinterConfig;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Settings extends Component
{
    // General Settings
    public string $store_name = '';
    public string $store_address = '';
    public string $store_phone = '';
    public float $tax_percentage = 0;
    public string $currency_symbol = 'Rp';
    public string $header_text = '';
    public string $footer_text = '';

    // Printer Config
    public string $paper_size = '58mm';
    public int $paper_width_px = 200;
    public string $font_family = 'monospace';
    public int $font_size_px = 12;
    public bool $auto_print = true;

    public function mount(): void
    {
        // Load general settings
        $this->store_name = Setting::get('general.store_name', 'Bakso Malang');
        $this->store_address = Setting::get('general.store_address', '');
        $this->store_phone = Setting::get('general.store_phone', '');
        $this->tax_percentage = (float) Setting::get('general.tax_percentage', 0);
        $this->currency_symbol = Setting::get('general.currency_symbol', 'Rp');
        $this->header_text = Setting::get('receipt.header_text', '');
        $this->footer_text = Setting::get('receipt.footer_text', '');

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

    public function saveGeneral(): void
    {
        Setting::set('general.store_name', $this->store_name, 'general');
        Setting::set('general.store_address', $this->store_address, 'general');
        Setting::set('general.store_phone', $this->store_phone, 'general');
        Setting::set('general.tax_percentage', $this->tax_percentage, 'general', 'float');
        Setting::set('general.currency_symbol', $this->currency_symbol, 'general');
        Setting::set('receipt.header_text', $this->header_text, 'receipt');
        Setting::set('receipt.footer_text', $this->footer_text, 'receipt');

        $this->dispatch('notify', type: 'success', message: 'Pengaturan umum berhasil disimpan');
    }

    public function savePrinter(): void
    {
        $printer = PrinterConfig::where('is_default', true)->first();
        
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

    public function render()
    {
        return view('livewire.admin.settings')
            ->title('Pengaturan');
    }
}
