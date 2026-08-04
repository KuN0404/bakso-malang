<?php

namespace Tests\Feature;

use App\Livewire\Admin\Categories;
use App\Livewire\Admin\Components;
use App\Livewire\Admin\Ingredients;
use App\Livewire\Admin\Products;
use App\Livewire\Admin\Users;
use App\Livewire\KitchenDisplay;
use App\Models\Category;
use App\Models\Component;
use App\Models\Product;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regresi untuk hardening kedua: sebelumnya, HALAMAN admin sudah di-gate lewat middleware
 * route (lihat AdminAccessControlTest), tapi AKSI TULIS individual di dalam komponen (save,
 * delete, adjust stok) tidak dicek permission granularnya sendiri — cukup punya izin "view"
 * halaman untuk bisa create/edit/delete/hapus. Ini reproduksi skenario nyata: role "Manager"
 * di seeder sengaja TIDAK diberi delete_products/delete_categories/delete_modifiers/apapun
 * user permission, tapi sebelum fix ini mereka tetap bisa menghapus lewat aksi Livewire.
 */
class AdminActionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function grantPermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $user->givePermissionTo($permissions);
    }

    private function makeCategory(): Category
    {
        return Category::create(['name' => 'Menu', 'slug' => 'menu-auth-' . uniqid(), 'sort_order' => 1, 'is_active' => true]);
    }

    private function makeProduct(): Product
    {
        return Product::create([
            'category_id' => $this->makeCategory()->id, 'name' => 'Bakso Auth Test',
            'slug' => 'bakso-auth-' . uniqid(), 'sku' => 'SKU-AUTH-' . uniqid(),
            'price' => 10000, 'is_active' => true, 'track_stock' => true, 'stock' => 20,
        ]);
    }

    // -----------------------------------------------------------------
    // Products
    // -----------------------------------------------------------------

    public function test_products_save_create_requires_create_products_permission(): void
    {
        $user = User::factory()->create(['username' => 'auth-prod-1']);
        $this->grantPermissions($user, ['view_products']); // hanya view, TIDAK create

        $category = $this->makeCategory();

        Livewire::actingAs($user)->test(Products::class)
            ->set('category_id', $category->id)
            ->set('name', 'Produk Baru')
            ->set('sku', 'SKU-NEW-1')
            ->set('price', 15000)
            ->call('save')
            ->assertForbidden();

        $this->assertFalse(Product::where('name', 'Produk Baru')->exists());
    }

    public function test_products_delete_requires_delete_products_permission(): void
    {
        $product = $this->makeProduct();

        // Meniru role Manager di seeder: punya view+edit tapi TIDAK delete_products.
        $manager = User::factory()->create(['username' => 'auth-prod-2']);
        $this->grantPermissions($manager, ['view_products', 'edit_products']);

        Livewire::actingAs($manager)->test(Products::class)
            ->call('delete', $product->id)
            ->assertForbidden();

        $this->assertNotNull(Product::find($product->id));
    }

    public function test_products_delete_succeeds_with_delete_products_permission(): void
    {
        $product = $this->makeProduct();

        $admin = User::factory()->create(['username' => 'auth-prod-3']);
        $this->grantPermissions($admin, ['view_products', 'delete_products']);

        Livewire::actingAs($admin)->test(Products::class)
            ->call('delete', $product->id)
            ->assertOk();

        $this->assertNull(Product::find($product->id));
    }

    public function test_products_stock_adjustment_requires_adjust_stock_permission(): void
    {
        $product = $this->makeProduct();

        $user = User::factory()->create(['username' => 'auth-prod-4']);
        $this->grantPermissions($user, ['view_products']); // tanpa adjust_stock

        Livewire::actingAs($user)->test(Products::class)
            ->call('openStockModal', $product->id)
            ->call('saveStock', 'add', 5, null)
            ->assertForbidden();

        $this->assertEquals(20, $product->fresh()->stock);
    }

    // -----------------------------------------------------------------
    // Categories
    // -----------------------------------------------------------------

    public function test_categories_delete_requires_delete_categories_permission(): void
    {
        $category = $this->makeCategory();

        $manager = User::factory()->create(['username' => 'auth-cat-1']);
        $this->grantPermissions($manager, ['view_categories', 'edit_categories']); // tanpa delete

        Livewire::actingAs($manager)->test(Categories::class)
            ->call('delete', $category->id)
            ->assertForbidden();

        $this->assertNotNull(Category::find($category->id));
    }

    // -----------------------------------------------------------------
    // Ingredients
    // -----------------------------------------------------------------

    public function test_ingredients_delete_requires_delete_ingredients_permission(): void
    {
        $ingredient = \App\Models\Ingredient::create([
            'code' => 'ING-AUTH-1', 'name' => 'Daging Sapi', 'unit' => 'kg', 'stock' => 10,
        ]);

        $user = User::factory()->create(['username' => 'auth-ing-1']);
        $this->grantPermissions($user, ['view_ingredients']); // tanpa delete

        Livewire::actingAs($user)->test(Ingredients::class)
            ->call('delete', $ingredient->id)
            ->assertForbidden();

        $this->assertNotNull(\App\Models\Ingredient::find($ingredient->id));
    }

    // -----------------------------------------------------------------
    // Components
    // -----------------------------------------------------------------

    public function test_components_stock_adjustment_requires_adjust_component_stock_permission(): void
    {
        $component = Component::create(['code' => 'CMP-AUTH-1', 'name' => 'Bakso Kecil', 'unit' => 'pcs', 'stock' => 50]);

        $user = User::factory()->create(['username' => 'auth-comp-1']);
        $this->grantPermissions($user, ['view_components']); // tanpa adjust_component_stock

        Livewire::actingAs($user)->test(Components::class)
            ->call('openStockModal', $component->id)
            ->set('stockAdjustmentAmount', 5)
            ->call('saveStock')
            ->assertForbidden();

        $this->assertEquals(50, (float) $component->fresh()->stock);
    }

    // -----------------------------------------------------------------
    // Users — role "Manager" di seeder tidak dapat izin users sama sekali,
    // jadi ini menguji kasir/staf biasa yang entah bagaimana dapat view_users tanpa create/delete.
    // -----------------------------------------------------------------

    public function test_users_delete_requires_delete_users_permission(): void
    {
        $target = User::factory()->create(['username' => 'target-user']);

        $staff = User::factory()->create(['username' => 'auth-users-1']);
        $this->grantPermissions($staff, ['view_users']); // tanpa delete_users

        Livewire::actingAs($staff)->test(Users::class)
            ->call('delete', $target->id)
            ->assertForbidden();

        $this->assertNotNull(User::find($target->id));
    }

    public function test_users_save_create_requires_create_users_permission(): void
    {
        Role::firstOrCreate(['name' => 'Kasir', 'guard_name' => 'web']);

        $staff = User::factory()->create(['username' => 'auth-users-2']);
        $this->grantPermissions($staff, ['view_users']); // tanpa create_users

        Livewire::actingAs($staff)->test(Users::class)
            ->set('username', 'usernew')
            ->set('name', 'User Baru')
            ->set('email', 'usernew@example.com')
            ->set('password', 'password')
            ->set('selectedRoles', ['Kasir'])
            ->call('save')
            ->assertForbidden();

        $this->assertFalse(User::where('email', 'usernew@example.com')->exists());
    }

    // -----------------------------------------------------------------
    // Kitchen Display — update_order_status terpisah dari view_kitchen_display
    // -----------------------------------------------------------------

    public function test_kitchen_mark_as_done_requires_update_order_status_permission(): void
    {
        $cashier = User::factory()->create(['username' => 'kitchen-cashier']);
        $shift   = Shift::create(['user_id' => $cashier->id, 'started_at' => now(), 'opening_cash' => 0, 'status' => 'open']);

        $category = $this->makeCategory();
        $product  = Product::create([
            'category_id' => $category->id, 'name' => 'Bakso Dapur', 'slug' => 'bakso-dapur-' . uniqid(),
            'sku' => 'SKU-DAPUR-' . uniqid(), 'price' => 10000, 'is_active' => true, 'track_stock' => false,
        ]);

        $transaction = Transaction::create([
            'user_id' => $cashier->id, 'shift_id' => $shift->id, 'invoice_number' => 'INV-KIT-1',
            'queue_number' => 1, 'subtotal' => 10000, 'discount_amount' => 0, 'tax_amount' => 0, 'total' => 10000,
            'paid_amount' => 10000, 'change_amount' => 0, 'payment_method' => 'cash',
            'source' => 'pos', 'status' => 'completed', 'order_type' => 'dine_in',
        ]);
        $detail = TransactionDetail::create([
            'transaction_id' => $transaction->id, 'product_id' => $product->id, 'product_name' => $product->name,
            'unit_price' => 10000, 'quantity' => 1, 'modifier_total' => 0, 'subtotal' => 10000,
        ]);

        $viewer = User::factory()->create(['username' => 'kitchen-viewer']);
        $this->grantPermissions($viewer, ['view_kitchen_display']); // tanpa update_order_status

        Livewire::actingAs($viewer)->test(KitchenDisplay::class)
            ->call('selectShift', $shift->id)
            ->call('markAsDone', $detail->id)
            ->assertForbidden();
    }

    // -----------------------------------------------------------------
    // Super Admin tetap bypass semua pengecekan aksi granular ini.
    // -----------------------------------------------------------------

    public function test_super_admin_can_still_perform_all_write_actions(): void
    {
        $product = $this->makeProduct();
        $superAdmin = User::factory()->create(['username' => 'auth-superadmin']);
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->assignRole('Super Admin');

        Livewire::actingAs($superAdmin)->test(Products::class)
            ->call('delete', $product->id)
            ->assertOk();

        $this->assertNull(Product::find($product->id));
    }
}
