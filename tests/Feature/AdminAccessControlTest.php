<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regresi untuk bug broken-access-control: seluruh halaman /admin/*, /print/*, dan /export/*
 * sebelumnya HANYA dilindungi middleware 'auth' — tidak ada 'can:xxx' sama sekali — padahal
 * sistem permission granular (RolesAndPermissionsSeeder) sudah didesain untuk membatasi akses
 * per role (Kasir/Kitchen seharusnya TIDAK bisa buka /admin/users, /admin/roles, /admin/settings,
 * atau laporan keuangan). Test ini memastikan setiap route benar-benar menolak (403) user yang
 * tidak punya permission terkait, dan menerima (bukan 403) user yang punya permission tersebut.
 */
class AdminAccessControlTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string}> label => [routeName, permission]
     */
    public static function protectedAdminRoutes(): array
    {
        return [
            'menu catalog'         => ['admin.menu.index', 'view_menu_catalog'],
            'hpp calculator'       => ['admin.hpp-calculator.index', 'view_hpp_calculator'],
            'categories'           => ['admin.categories.index', 'view_categories'],
            'products'             => ['admin.products.index', 'view_products'],
            'modifiers'            => ['admin.modifiers.index', 'view_modifiers'],
            'payment sources'      => ['admin.payment-sources.index', 'manage_payment_sources'],
            'service areas'        => ['admin.service-areas.index', 'manage_service_areas'],
            'ingredients'          => ['admin.ingredients.index', 'view_ingredients'],
            'components'           => ['admin.components.index', 'view_components'],
            'purchases'            => ['admin.purchases.index', 'view_purchases'],
            'productions'          => ['admin.productions.index', 'view_productions'],
            'shifts list'          => ['admin.shifts.index', 'view_own_shifts'],
            'returns'              => ['admin.returns', 'view_returns'],
            'report transactions'  => ['admin.reports.transactions', 'view_transactions'],
            'report sales'         => ['admin.reports.sales', 'view_sales_reports'],
            'report products'      => ['admin.reports.products', 'view_sales_reports'],
            'report inventory'     => ['admin.reports.inventory', 'view_inventory_reports'],
            'report shifts'        => ['admin.reports.shifts', 'view_all_shifts'],
            'users'                => ['admin.users.index', 'view_users'],
            'roles'                => ['admin.roles.index', 'manage_roles'],
            'settings'             => ['admin.settings.index', 'manage_settings'],
            'whatsapp'             => ['admin.whatsapp.index', 'manage_whatsapp'],
        ];
    }

    private function grantPermission(User $user, string $permission): void
    {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user->givePermissionTo($permission);
    }

    #[DataProvider('protectedAdminRoutes')]
    public function test_admin_route_rejects_user_without_permission(string $routeName, string $permission): void
    {
        $user = User::factory()->create(['username' => 'noperm_' . \Illuminate\Support\Str::random(8)]);
        // Permission record harus ada di DB supaya middleware 'can:' punya sesuatu untuk dicek,
        // tapi user ini sengaja TIDAK diberi permission tersebut.
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);

        $this->actingAs($user)->get(route($routeName))->assertForbidden();
    }

    #[DataProvider('protectedAdminRoutes')]
    public function test_admin_route_allows_user_with_permission(string $routeName, string $permission): void
    {
        $user = User::factory()->create(['username' => 'hasperm_' . \Illuminate\Support\Str::random(8)]);
        $this->grantPermission($user, $permission);

        $this->actingAs($user)->get(route($routeName))->assertOk();
    }

    public function test_super_admin_bypasses_all_permission_checks(): void
    {
        $user = User::factory()->create(['username' => 'superadmin-test']);
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $user->assignRole('Super Admin');

        foreach (self::protectedAdminRoutes() as [$routeName, $permission]) {
            $this->actingAs($user)->get(route($routeName))->assertOk();
        }
    }

    // -----------------------------------------------------------------
    // Route dengan parameter (route-model-binding) — dicek terpisah karena butuh data.
    // -----------------------------------------------------------------

    public function test_product_detail_route_requires_view_products_permission(): void
    {
        $category = Category::create(['name' => 'Menu', 'slug' => 'menu-admin-test', 'sort_order' => 1, 'is_active' => true]);
        $product  = Product::create([
            'category_id' => $category->id, 'name' => 'Bakso Admin Test', 'slug' => 'bakso-admin-test',
            'sku' => 'SKU-ADM-1', 'price' => 10000, 'is_active' => true, 'track_stock' => false,
        ]);

        $noPerm = User::factory()->create(['username' => 'noperm-pd']);
        $this->actingAs($noPerm)->get(route('admin.products.show', $product))->assertForbidden();

        $hasPerm = User::factory()->create(['username' => 'hasperm-pd']);
        $this->grantPermission($hasPerm, 'view_products');
        $this->actingAs($hasPerm)->get(route('admin.products.show', $product))->assertOk();
    }

    public function test_export_transactions_requires_view_transactions_permission(): void
    {
        $noPerm = User::factory()->create(['username' => 'noperm-export']);
        $this->actingAs($noPerm)->get(route('export.transactions'))->assertForbidden();
    }

    public function test_print_sales_report_requires_view_sales_reports_permission(): void
    {
        $noPerm = User::factory()->create(['username' => 'noperm-print-sales']);
        $this->actingAs($noPerm)->get(route('print.sales-report'))->assertForbidden();
    }

    public function test_export_shifts_requires_view_all_shifts_permission_not_own_shifts_only(): void
    {
        // Kasir biasa (cuma view_own_shifts) TIDAK boleh export laporan SEMUA shift kasir lain.
        $kasir = User::factory()->create(['username' => 'kasir-export-shifts']);
        $this->grantPermission($kasir, 'view_own_shifts');

        $this->actingAs($kasir)->get(route('export.shifts'))->assertForbidden();
    }
}
