<?php

/**
 * Permission Labels Configuration
 * 
 * Mapping permission names to Indonesian translations
 * Usage: config('permissions.labels.permission_name')
 */

return [
    'labels' => [
        // Categories
        'view_categories' => 'Lihat Kategori',
        'create_categories' => 'Buat Kategori',
        'edit_categories' => 'Edit Kategori',
        'delete_categories' => 'Hapus Kategori',
        
        // Products
        'view_products' => 'Lihat Produk',
        'create_products' => 'Buat Produk',
        'edit_products' => 'Edit Produk',
        'delete_products' => 'Hapus Produk',
        'adjust_stock' => 'Atur Stok',
        
        // Modifiers
        'view_modifiers' => 'Lihat Modifier',
        'create_modifiers' => 'Buat Modifier',
        'edit_modifiers' => 'Edit Modifier',
        'delete_modifiers' => 'Hapus Modifier',
        
        // Transactions (POS)
        'access_pos' => 'Akses POS Kasir',
        'create_transactions' => 'Buat Transaksi',
        'view_transactions' => 'Lihat Semua Transaksi',
        'view_own_transactions' => 'Lihat Transaksi Sendiri',
        'cancel_transactions' => 'Batalkan Transaksi',
        
        // Shifts
        'open_shift' => 'Buka Shift',
        'close_shift' => 'Tutup Shift',
        'view_own_shifts' => 'Lihat Shift Sendiri',
        'view_all_shifts' => 'Lihat Semua Shift',
        'add_shift_expense' => 'Tambah Pengeluaran Shift',
        
        // Reports
        'view_sales_reports' => 'Lihat Laporan Penjualan',
        'view_financial_reports' => 'Lihat Laporan Keuangan',
        'view_peak_hours_reports' => 'Lihat Laporan Jam Sibuk',
        'view_cancellation_reports' => 'Lihat Laporan Pembatalan',
        'view_inventory_reports' => 'Lihat Laporan Inventori & Produksi',
        'export_reports' => 'Ekspor Laporan',
        
        // Settings
        'manage_settings' => 'Kelola Pengaturan',
        'manage_printers' => 'Kelola Printer',
        'manage_payment_sources' => 'Kelola Metode Pembayaran',
        
        // Users & Roles
        'view_users' => 'Lihat Pengguna',
        'create_users' => 'Buat Pengguna',
        'edit_users' => 'Edit Pengguna',
        'delete_users' => 'Hapus Pengguna',
        'manage_roles' => 'Kelola Peran',

        // Inventory & Production
        'manage_unlimited_stock' => 'Kelola Stok Unlimited Produk',
        'view_ingredients' => 'Lihat Bahan Baku',
        'create_ingredients' => 'Buat Bahan Baku',
        'edit_ingredients' => 'Edit Bahan Baku',
        'delete_ingredients' => 'Hapus Bahan Baku',
        'view_purchases' => 'Lihat Pembelian Stok',
        'create_purchases' => 'Buat Pembelian Stok',
        'view_productions' => 'Lihat Repacking / Produksi',
        'create_productions' => 'Buat Repacking / Produksi',
        'view_hpp_calculator' => 'Lihat & Gunakan Kalkulator HPP',
        'apply_hpp_calculator' => 'Terapkan HPP ke Produk',
        'view_menu_catalog' => 'Lihat Katalog Menu',

        // Self Order
        'view_self_orders'           => 'Lihat Dashboard Self Order',
        'manage_self_orders'         => 'Kelola Self Order (Proses/Status)',
        'accept_self_order_payment'  => 'Konfirmasi Pembayaran Self Order',
        'cancel_self_order'          => 'Batalkan Self Order',
        'print_self_order_receipt'   => 'Cetak Struk Self Order',
    ],
    
    /**
     * Group permissions by category for better UI display
     */
    'groups' => [
        'Kategori' => [
            'view_categories',
            'create_categories',
            'edit_categories',
            'delete_categories',
        ],
        'Produk' => [
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'adjust_stock',
            'manage_unlimited_stock',
            'view_menu_catalog',
        ],
        'Modifier' => [
            'view_modifiers',
            'create_modifiers',
            'edit_modifiers',
            'delete_modifiers',
        ],
        'Inventori & Produksi' => [
            'view_ingredients',
            'create_ingredients',
            'edit_ingredients',
            'delete_ingredients',
            'view_purchases',
            'create_purchases',
            'view_productions',
            'create_productions',
            'view_hpp_calculator',
            'apply_hpp_calculator',
        ],
        'Transaksi' => [
            'access_pos',
            'create_transactions',
            'view_transactions',
            'view_own_transactions',
            'cancel_transactions',
        ],
        'Shift' => [
            'open_shift',
            'close_shift',
            'view_own_shifts',
            'view_all_shifts',
            'add_shift_expense',
        ],
        'Laporan' => [
            'view_sales_reports',
            'view_financial_reports',
            'view_peak_hours_reports',
            'view_cancellation_reports',
            'view_inventory_reports',
            'export_reports',
        ],
        'Pengaturan' => [
            'manage_settings',
            'manage_printers',
            'manage_payment_sources',
        ],
        'Pengguna & Peran' => [
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'manage_roles',
        ],
        'Self Order' => [
            'view_self_orders',
            'manage_self_orders',
            'accept_self_order_payment',
            'cancel_self_order',
            'print_self_order_receipt',
        ],
    ],
];
