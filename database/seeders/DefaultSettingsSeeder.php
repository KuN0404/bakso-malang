<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use App\Models\PrinterConfig;
use App\Models\PaymentSource;

class DefaultSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // General Settings
        $generalSettings = [
            ['group' => 'general', 'key' => 'store_name', 'value' => 'Bakso Malang', 'type' => 'string', 'description' => 'Nama toko/usaha'],
            ['group' => 'general', 'key' => 'store_address', 'value' => 'Jl. Contoh No. 123, Malang', 'type' => 'string', 'description' => 'Alamat toko'],
            ['group' => 'general', 'key' => 'store_phone', 'value' => '08123456789', 'type' => 'string', 'description' => 'Nomor telepon toko'],
            ['group' => 'general', 'key' => 'tax_percentage', 'value' => '0', 'type' => 'integer', 'description' => 'Persentase pajak (0 = tidak ada pajak)'],
            ['group' => 'general', 'key' => 'currency', 'value' => 'IDR', 'type' => 'string', 'description' => 'Mata uang'],
        ];

        foreach ($generalSettings as $setting) {
            Setting::firstOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                $setting
            );
        }

        // Receipt Settings
        $receiptSettings = [
            ['group' => 'receipt', 'key' => 'header_text', 'value' => 'Terima Kasih Atas Kunjungan Anda!', 'type' => 'string', 'description' => 'Teks header struk'],
            ['group' => 'receipt', 'key' => 'footer_text', 'value' => 'Kritik & Saran: 08123456789', 'type' => 'string', 'description' => 'Teks footer struk'],
            ['group' => 'receipt', 'key' => 'show_logo', 'value' => '1', 'type' => 'boolean', 'description' => 'Tampilkan logo di struk'],
            ['group' => 'receipt', 'key' => 'auto_print', 'value' => '1', 'type' => 'boolean', 'description' => 'Auto print setelah transaksi'],
        ];

        foreach ($receiptSettings as $setting) {
            Setting::firstOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                $setting
            );
        }

        // Default Printer Config
        PrinterConfig::firstOrCreate(
            ['name' => 'Printer Utama'],
            [
                'paper_size' => '58mm',
                'is_default' => true,
                'auto_print' => true,
                'margins' => ['top' => 0, 'right' => 2, 'bottom' => 0, 'left' => 2],
                'font_family' => 'monospace',
                'font_size_px' => 12,
                'show_logo' => true,
                'show_footer' => true,
            ]
        );

        // Default Payment Sources
        $paymentSources = [
            ['name' => 'Cash', 'type' => 'cash', 'icon' => 'cash', 'is_active' => true, 'sort_order' => 1],
            ['name' => 'QRIS', 'type' => 'qris', 'icon' => 'qris', 'is_active' => true, 'sort_order' => 2],
            ['name' => 'Transfer Bank', 'type' => 'transfer', 'icon' => 'bank', 'is_active' => true, 'sort_order' => 3],
            ['name' => 'Kartu Debit', 'type' => 'card', 'icon' => 'card', 'is_active' => true, 'sort_order' => 4],
            ['name' => 'GoPay', 'type' => 'ewallet', 'icon' => 'gopay', 'is_active' => true, 'sort_order' => 5],
            ['name' => 'OVO', 'type' => 'ewallet', 'icon' => 'ovo', 'is_active' => true, 'sort_order' => 6],
            ['name' => 'Dana', 'type' => 'ewallet', 'icon' => 'dana', 'is_active' => true, 'sort_order' => 7],
        ];

        foreach ($paymentSources as $source) {
            PaymentSource::firstOrCreate(
                ['name' => $source['name']],
                $source
            );
        }

        $this->command->info('Default settings, printer config, and payment sources seeded successfully!');
    }
}
