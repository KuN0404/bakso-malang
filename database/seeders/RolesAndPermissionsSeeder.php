<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions (these are fixed/predefined)
        $permissions = [
            // Products
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'adjust_stock',
            'manage_unlimited_stock',
            
            // Categories
            'view_categories',
            'create_categories',
            'edit_categories',
            'delete_categories',
            
            // Modifiers
            'view_modifiers',
            'create_modifiers',
            'edit_modifiers',
            'delete_modifiers',
            
            // Transactions (POS)
            'access_pos',
            'create_transactions',
            'view_transactions',
            'view_own_transactions',
            'cancel_transactions',
            
            // Shifts
            'open_shift',
            'close_shift',
            'view_own_shifts',
            'view_all_shifts',
            'add_shift_expense',
            
            // Reports
            'view_sales_reports',
            'view_financial_reports',
            'view_peak_hours_reports',
            'view_cancellation_reports',
            'view_inventory_reports',
            'view_returns',
            'export_reports',
            
            // Settings
            'manage_settings',
            'manage_printers',
            'manage_payment_sources',
            'manage_service_areas',
            'manage_pagers',

            // Users & Roles
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'manage_roles',
            
            // Kitchen / Service Display
            'view_kitchen_display',
            'update_order_status',

            // Inventory & Production
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
            'view_menu_catalog',

            // Components (Item Setengah Jadi)
            'view_components',
            'create_components',
            'edit_components',
            'delete_components',
            'adjust_component_stock',

            // Self Order
            'view_self_orders',
            'manage_self_orders',
            'accept_self_order_payment',
            'cancel_self_order',
            'print_self_order_receipt',

            // Pelanggan & Blacklist
            'view_customers',
            'view_phone_blacklist',
            'manage_phone_blacklist',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles with default permissions
        // Note: Users can have multiple roles (multi-role assignment feature)

        // Super Admin - has all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->syncPermissions($permissions);

        // Manager - can manage products, view reports, manage shifts
        $manager = Role::firstOrCreate(['name' => 'Manager']);
        $manager->syncPermissions([
            'view_products', 'create_products', 'edit_products', 'adjust_stock', 'manage_unlimited_stock', 'view_menu_catalog',
            'view_categories', 'create_categories', 'edit_categories',
            'view_modifiers', 'create_modifiers', 'edit_modifiers',
            'view_ingredients', 'create_ingredients', 'edit_ingredients', 'delete_ingredients',
            'view_purchases', 'create_purchases', 'view_productions', 'create_productions', 'view_hpp_calculator', 'apply_hpp_calculator',
            'view_components', 'create_components', 'edit_components', 'delete_components', 'adjust_component_stock',
            'access_pos', 'view_transactions', 'cancel_transactions',
            'open_shift', 'close_shift', 'view_own_shifts', 'view_all_shifts', 'add_shift_expense',
            'view_sales_reports', 'view_financial_reports', 'view_peak_hours_reports', 'view_cancellation_reports', 'view_inventory_reports', 'view_returns', 'export_reports',
            'manage_payment_sources', 'manage_service_areas', 'manage_pagers',
            'view_kitchen_display', 'update_order_status',
            // Self Order
            'view_self_orders', 'manage_self_orders', 'accept_self_order_payment', 'cancel_self_order', 'print_self_order_receipt',
            // Pelanggan & Blacklist
            'view_customers', 'view_phone_blacklist', 'manage_phone_blacklist',
        ]);

        // Kasir - can use POS, manage own shifts
        $kasir = Role::firstOrCreate(['name' => 'Kasir']);
        $kasir->syncPermissions([
            'view_products',
            'access_pos', 'create_transactions', 'view_own_transactions',
            'open_shift', 'close_shift', 'view_own_shifts', 'add_shift_expense',
            // Self Order permissions untuk kasir
            'view_self_orders', 'manage_self_orders', 'accept_self_order_payment',
            'cancel_self_order', 'print_self_order_receipt',
            // Pelanggan & Blacklist — kasir bisa blokir langsung dari lapangan
            'view_customers', 'view_phone_blacklist', 'manage_phone_blacklist',
        ]);

        // Kitchen - can view orders (read-only for kitchen display)
        $kitchen = Role::firstOrCreate(['name' => 'Kitchen']);
        $kitchen->syncPermissions([
            'view_transactions',
            'view_kitchen_display', 
            'update_order_status',
        ]);

        // Create default Super Admin user
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@baksomalang.com',
                'password' => bcrypt('password'),
            ]
        );
        $admin->assignRole('Super Admin');

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Default admin: admin / password');
    }
}
