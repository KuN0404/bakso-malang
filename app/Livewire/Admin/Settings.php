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
        // Load general settings (key without group prefix, group as separate param)
        $this->store_name = Setting::get('store_name', 'Bakso Malang', 'general');
        $this->store_address = Setting::get('store_address', '', 'general');
        $this->store_phone = Setting::get('store_phone', '', 'general');
        $this->tax_percentage = (float) Setting::get('tax_percentage', 0, 'general');
        $this->currency_symbol = Setting::get('currency_symbol', 'Rp', 'general');
        $this->header_text = Setting::get('header_text', '', 'receipt');
        $this->footer_text = Setting::get('footer_text', '', 'receipt');

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
        Setting::set('store_name', $this->store_name, 'general');
        Setting::set('store_address', $this->store_address, 'general');
        Setting::set('store_phone', $this->store_phone, 'general');
        Setting::set('tax_percentage', $this->tax_percentage, 'general', 'float');
        Setting::set('currency_symbol', $this->currency_symbol, 'general');
        Setting::set('header_text', $this->header_text, 'receipt');
        Setting::set('footer_text', $this->footer_text, 'receipt');

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
