<?php

namespace Tests\Feature;

use App\Actions\SelfOrder\ClaimSelfOrderAction;
use App\Actions\SelfOrder\UpdateSelfOrderStatusAction;
use App\Enums\SelfOrderStatus;
use App\Livewire\Admin\SelfOrderDashboard;
use App\Livewire\PosCheckout;
use App\Models\Category;
use App\Models\PaymentSource;
use App\Models\Product;
use App\Models\SelfOrder;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PosSelfOrderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Shift punya static cache level-class (lihat Shift::$cachedTodayShift dkk) yang hanya
        // di-invalidate lewat getOrCreateTodayShift()/close(). Karena test lain di suite ini
        // (mis. helper openShiftFor()) membuat Shift langsung tanpa lewat jalur itu, cache bisa
        // basi antar test method dalam proses PHPUnit yang sama. Reset eksplisit di sini.
        Shift::clearStaticCache();

        $this->category = Category::create([
            'name' => 'Menu', 'slug' => 'menu', 'sort_order' => 1, 'is_active' => true,
        ]);

        PaymentSource::create(['name' => 'Cash', 'type' => 'cash', 'is_active' => true, 'sort_order' => 1]);
    }

    private function makeCashier(string $username): User
    {
        return User::factory()->create(['username' => $username]);
    }

    private function openShiftFor(User $user): Shift
    {
        $shift = Shift::create([
            'user_id' => $user->id, 'started_at' => now(), 'opening_cash' => 0, 'status' => 'open',
        ]);
        Shift::clearStaticCache();

        return $shift;
    }

    private function grantPermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $user->givePermissionTo($permissions);
    }

    private function makeSelfOrder(array $overrides = []): SelfOrder
    {
        static $i = 0;
        $i++;

        return SelfOrder::create(array_merge([
            'queue_number'   => $i,
            'customer_name'  => 'Pelanggan',
            'customer_phone' => '08120000000' . $i,
            'subtotal'       => 10000,
            'tax_amount'     => 0,
            'total'          => 10000,
            'order_type'     => 'dine_in',
            'payment_method' => 'qris',
            'status'         => SelfOrderStatus::Paid->value,
            'customer_ip'    => '127.0.0.1',
        ], $overrides));
    }

    // -----------------------------------------------------------------
    // Fix #1: Shift tidak boleh ditutup jika masih ada Self Order aktif
    // -----------------------------------------------------------------

    public function test_close_shift_modal_blocked_when_active_self_order_tied_to_shift(): void
    {
        $cashier = $this->makeCashier('kasir-close-1');
        $shift   = $this->openShiftFor($cashier);

        $this->makeSelfOrder(['shift_id' => $shift->id, 'status' => SelfOrderStatus::Paid->value]);

        $this->actingAs($cashier);

        Livewire::test(PosCheckout::class)
            ->call('openCloseShiftModal')
            ->assertSet('showCloseShiftModal', false)
            ->assertDispatched('notify');
    }

    public function test_close_shift_modal_allowed_when_self_orders_for_shift_are_all_completed(): void
    {
        $cashier = $this->makeCashier('kasir-close-2');
        $shift   = $this->openShiftFor($cashier);

        $this->makeSelfOrder(['shift_id' => $shift->id, 'status' => SelfOrderStatus::Completed->value]);
        $this->makeSelfOrder(['shift_id' => $shift->id, 'status' => SelfOrderStatus::Cancelled->value]);

        $this->actingAs($cashier);

        Livewire::test(PosCheckout::class)
            ->call('openCloseShiftModal')
            ->assertSet('showCloseShiftModal', true);
    }

    public function test_close_shift_modal_ignores_active_self_orders_belonging_to_other_shifts(): void
    {
        $cashier      = $this->makeCashier('kasir-close-3');
        $otherCashier = $this->makeCashier('kasir-close-4');
        $shift        = $this->openShiftFor($cashier);
        $otherShift   = $this->openShiftFor($otherCashier);

        // Self order aktif milik shift KASIR LAIN — tidak boleh menghalangi shift kasir ini
        $this->makeSelfOrder(['shift_id' => $otherShift->id, 'status' => SelfOrderStatus::Paid->value]);

        $this->actingAs($cashier);

        Livewire::test(PosCheckout::class)
            ->call('openCloseShiftModal')
            ->assertSet('showCloseShiftModal', true);
    }

    // -----------------------------------------------------------------
    // Regresi: UpdateSelfOrderStatusAction dulu fallback shift_id ke 0 (bukan null)
    // saat tidak ada shift terbuka -> FK constraint violation di produksi.
    // -----------------------------------------------------------------

    public function test_update_status_throws_domain_exception_instead_of_db_error_when_no_shift_open(): void
    {
        $cashier = $this->makeCashier('kasir-status-1');
        // Sengaja TIDAK membuka shift untuk kasir ini.

        $order = $this->makeSelfOrder(['status' => SelfOrderStatus::Paid->value]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('belum membuka shift');

        app(UpdateSelfOrderStatusAction::class)->execute($order->id, SelfOrderStatus::Processing, $cashier->id);
    }

    public function test_update_status_does_not_borrow_another_cashiers_shift(): void
    {
        $cashierA = $this->makeCashier('kasir-status-2');
        $cashierB = $this->makeCashier('kasir-status-3');
        $this->openShiftFor($cashierB); // hanya kasir B yang buka shift

        $order = $this->makeSelfOrder(['status' => SelfOrderStatus::Paid->value]);

        $this->expectException(\DomainException::class);

        // Kasir A mencoba proses order padahal belum buka shift sendiri —
        // TIDAK boleh diam-diam memakai shift milik kasir B.
        app(UpdateSelfOrderStatusAction::class)->execute($order->id, SelfOrderStatus::Processing, $cashierA->id);
    }

    public function test_update_status_assigns_cashiers_own_open_shift_on_success(): void
    {
        $cashier = $this->makeCashier('kasir-status-4');
        $shift   = $this->openShiftFor($cashier);

        $order = $this->makeSelfOrder(['status' => SelfOrderStatus::Paid->value]);

        $updated = app(UpdateSelfOrderStatusAction::class)->execute($order->id, SelfOrderStatus::Processing, $cashier->id);

        $this->assertEquals($shift->id, $updated->shift_id);
        $this->assertEquals($cashier->id, $updated->processed_by);
        $this->assertEquals(SelfOrderStatus::Processing, $updated->status);
    }

    // -----------------------------------------------------------------
    // Fix #2 & #3: Urutan FIFO & tidak hilang lintas hari (scope model)
    // -----------------------------------------------------------------

    public function test_paid_tab_scope_orders_oldest_first_regardless_of_date(): void
    {
        $yesterdayOrder = $this->makeSelfOrder(['status' => SelfOrderStatus::Paid->value]);
        $yesterdayOrder->forceFill(['created_at' => now()->subDay(), 'updated_at' => now()->subDay()])->save();

        $todayOrder = $this->makeSelfOrder(['status' => SelfOrderStatus::Paid->value]);

        $ids = SelfOrder::forPaidTab()->pluck('id')->all();

        // Order dari kemarin (belum selesai) HARUS tetap tampil dan berada di urutan PALING ATAS (FIFO)
        $this->assertEquals([$yesterdayOrder->id, $todayOrder->id], $ids);
    }

    public function test_waiting_tab_scope_orders_oldest_first_regardless_of_date(): void
    {
        $older = $this->makeSelfOrder(['status' => SelfOrderStatus::WaitingPayment->value, 'payment_method' => 'cash_on_counter']);
        $older->forceFill(['created_at' => now()->subHours(2), 'updated_at' => now()->subHours(2)])->save();

        $newer = $this->makeSelfOrder(['status' => SelfOrderStatus::WaitingPayment->value, 'payment_method' => 'cash_on_counter']);

        $ids = SelfOrder::forWaitingTab()->pluck('id')->all();

        $this->assertEquals([$older->id, $newer->id], $ids);
    }

    // -----------------------------------------------------------------
    // Fix #4: Toast notifikasi saat ada Self Order baru masuk
    // -----------------------------------------------------------------

    public function test_pos_shows_toast_when_self_order_count_increases(): void
    {
        $cashier = $this->makeCashier('kasir-toast-1');
        $this->actingAs($cashier);

        $component = Livewire::test(PosCheckout::class);
        // Baseline saat mount: belum ada self order sama sekali -> tidak ada toast di render awal.

        $this->makeSelfOrder(['status' => SelfOrderStatus::Paid->value]);

        // Simulasikan wire:poll — sekarang count naik dari 0 ke 1, harus toast.
        $component->call('checkForNewSelfOrders')
            ->assertDispatched('notify');
    }

    public function test_pos_does_not_toast_on_initial_load_for_pre_existing_self_orders(): void
    {
        $cashier = $this->makeCashier('kasir-toast-2');
        $this->makeSelfOrder(['status' => SelfOrderStatus::Paid->value]);

        $this->actingAs($cashier);

        // Order sudah ada SEBELUM halaman POS dibuka -> baseline mount menyerapnya, tidak perlu toast.
        Livewire::test(PosCheckout::class)
            ->assertNotDispatched('notify');
    }

    // -----------------------------------------------------------------
    // Fitur: Tombol "Buka Shift" manual di header POS (bukan implisit via transaksi),
    // dengan modal konfirmasi custom (bukan window.confirm() browser).
    // -----------------------------------------------------------------

    public function test_open_shift_prompt_shows_modal_when_no_shift_open(): void
    {
        $cashier = $this->makeCashier('kasir-openshift-1');
        $this->actingAs($cashier);

        Livewire::test(PosCheckout::class)
            ->call('openShiftPrompt')
            ->assertSet('showOpenShiftModal', true);

        $this->assertNull(Shift::where('user_id', $cashier->id)->where('status', 'open')->first());
    }

    public function test_confirm_open_shift_creates_shift_and_closes_modal(): void
    {
        $cashier = $this->makeCashier('kasir-openshift-2');
        $this->actingAs($cashier);

        Livewire::test(PosCheckout::class)
            ->call('openShiftPrompt')
            ->assertSet('showOpenShiftModal', true)
            ->call('confirmOpenShift')
            ->assertSet('showOpenShiftModal', false)
            ->assertDispatched('notify');

        $shift = Shift::where('user_id', $cashier->id)->where('status', 'open')->first();
        $this->assertNotNull($shift);
    }

    public function test_open_shift_prompt_does_not_open_modal_when_shift_already_open(): void
    {
        $cashier = $this->makeCashier('kasir-openshift-3');
        $this->openShiftFor($cashier);
        $this->actingAs($cashier);

        Livewire::test(PosCheckout::class)
            ->call('openShiftPrompt')
            ->assertSet('showOpenShiftModal', false)
            ->assertDispatched('notify');

        // Tidak membuat shift kedua untuk kasir yang sama pada hari yang sama.
        $this->assertEquals(1, Shift::where('user_id', $cashier->id)->count());
    }

    public function test_confirm_open_shift_is_idempotent_against_double_click(): void
    {
        $cashier = $this->makeCashier('kasir-openshift-4');
        $this->actingAs($cashier);

        $component = Livewire::test(PosCheckout::class)->call('openShiftPrompt');
        $component->call('confirmOpenShift');
        $component->call('confirmOpenShift'); // simulasi double click

        $this->assertEquals(1, Shift::where('user_id', $cashier->id)->where('status', 'open')->count());
    }

    // -----------------------------------------------------------------
    // Fitur: Tab ke-3 "Pesanan Diambil" — pool unclaimed vs claimed terpisah jelas.
    // -----------------------------------------------------------------

    public function test_paid_tab_scope_excludes_claimed_orders(): void
    {
        $cashier = $this->makeCashier('kasir-scope-1');

        $unclaimed = $this->makeSelfOrder(['status' => SelfOrderStatus::Paid->value]);
        $claimed   = $this->makeSelfOrder(['status' => SelfOrderStatus::Paid->value, 'processed_by' => $cashier->id]);

        $ids = SelfOrder::forPaidTab()->pluck('id')->all();

        $this->assertEquals([$unclaimed->id], $ids);
    }

    public function test_waiting_tab_scope_excludes_claimed_orders(): void
    {
        $cashier = $this->makeCashier('kasir-scope-2');

        $unclaimed = $this->makeSelfOrder(['status' => SelfOrderStatus::WaitingPayment->value, 'payment_method' => 'cash_on_counter']);
        $claimed   = $this->makeSelfOrder(['status' => SelfOrderStatus::WaitingPayment->value, 'payment_method' => 'cash_on_counter', 'processed_by' => $cashier->id]);

        $ids = SelfOrder::forWaitingTab()->pluck('id')->all();

        $this->assertEquals([$unclaimed->id], $ids);
    }

    public function test_claimed_tab_scope_shows_claimed_orders_across_both_payment_methods(): void
    {
        $cashier = $this->makeCashier('kasir-claimedtab-1');

        $claimedQris = $this->makeSelfOrder([
            'status' => SelfOrderStatus::Paid->value, 'payment_method' => 'qris', 'processed_by' => $cashier->id,
        ]);
        $claimedCash = $this->makeSelfOrder([
            'status' => SelfOrderStatus::WaitingPayment->value, 'payment_method' => 'cash_on_counter', 'processed_by' => $cashier->id,
        ]);
        $unclaimedPaid    = $this->makeSelfOrder(['status' => SelfOrderStatus::Paid->value]);
        $completedClaimed = $this->makeSelfOrder(['status' => SelfOrderStatus::Completed->value, 'processed_by' => $cashier->id]);

        $ids = SelfOrder::forClaimedTab()->pluck('id')->all();

        sort($ids);
        $expected = [$claimedQris->id, $claimedCash->id];
        sort($expected);

        $this->assertEquals($expected, $ids);
        $this->assertNotContains($unclaimedPaid->id, $ids);
        $this->assertNotContains($completedClaimed->id, $ids); // sudah selesai, bukan lagi "sedang dikerjakan"
    }

    public function test_claimed_tab_scope_can_filter_to_only_one_cashier(): void
    {
        $cashierA = $this->makeCashier('kasir-claimedtab-2');
        $cashierB = $this->makeCashier('kasir-claimedtab-3');

        $orderA = $this->makeSelfOrder(['status' => SelfOrderStatus::Paid->value, 'processed_by' => $cashierA->id]);
        $orderB = $this->makeSelfOrder(['status' => SelfOrderStatus::Paid->value, 'processed_by' => $cashierB->id]);

        $ids = SelfOrder::forClaimedTab($cashierA->id)->pluck('id')->all();

        $this->assertEquals([$orderA->id], $ids);
    }

    public function test_claiming_order_from_dashboard_moves_it_to_claimed_tab(): void
    {
        $cashier = $this->makeCashier('kasir-dashboard-1');
        $this->grantPermissions($cashier, ['manage_self_orders', 'view_self_orders']);
        $this->openShiftFor($cashier);
        $this->actingAs($cashier);

        $order = $this->makeSelfOrder(['status' => SelfOrderStatus::Paid->value, 'order_type' => 'take_away']);

        Livewire::test(SelfOrderDashboard::class)
            ->set('activeTab', 'paid')
            ->call('claimOrder', $order->id)
            ->assertSet('activeTab', 'claimed');

        $this->assertEquals($cashier->id, $order->fresh()->processed_by);
        $this->assertContains($order->id, SelfOrder::forClaimedTab()->pluck('id')->all());
        $this->assertNotContains($order->id, SelfOrder::forPaidTab()->pluck('id')->all());
    }

    // -----------------------------------------------------------------
    // Keamanan: klaim pesanan mandiri wajib shift terbuka, dan otomatis
    // menawarkan modal "Buka Shift" alih-alih error generik.
    // -----------------------------------------------------------------

    public function test_claim_order_prompts_open_shift_instead_of_generic_error_when_no_shift(): void
    {
        $cashier = $this->makeCashier('kasir-noshift-1');
        $this->grantPermissions($cashier, ['manage_self_orders', 'view_self_orders']);
        $this->actingAs($cashier);

        $order = $this->makeSelfOrder(['status' => SelfOrderStatus::Paid->value, 'order_type' => 'take_away']);

        Livewire::test(SelfOrderDashboard::class)
            ->call('claimOrder', $order->id)
            ->assertDispatched('open-shift-requested')
            ->assertSet('activeTab', 'paid'); // tidak pindah tab karena klaim gagal

        $this->assertNull($order->fresh()->processed_by);
    }

    public function test_pos_checkout_opens_shift_modal_when_notified_by_child_component(): void
    {
        $cashier = $this->makeCashier('kasir-noshift-2');
        $this->actingAs($cashier);

        Livewire::test(PosCheckout::class)
            ->call('handleOpenShiftRequested')
            ->assertSet('showOpenShiftModal', true);
    }

    // -----------------------------------------------------------------
    // Keamanan: tombol proses di tab "Diambil" cuma untuk kasir yang mengambilnya.
    // -----------------------------------------------------------------

    public function test_claimed_tab_hides_process_button_from_non_owner_cashier(): void
    {
        $owner   = $this->makeCashier('kasir-owner-1');
        $viewer  = $this->makeCashier('kasir-viewer-1');
        $this->grantPermissions($viewer, ['manage_self_orders', 'view_self_orders']);
        $this->openShiftFor($viewer);

        $order = $this->makeSelfOrder(['status' => SelfOrderStatus::Paid->value, 'processed_by' => $owner->id]);

        $this->actingAs($viewer);

        Livewire::test(SelfOrderDashboard::class)
            ->set('activeTab', 'claimed')
            ->assertDontSee('→ Diproses')
            ->assertSee('Bukan pesanan Anda');
    }

    public function test_claimed_tab_shows_process_button_to_owner_cashier(): void
    {
        $owner = $this->makeCashier('kasir-owner-2');
        $this->grantPermissions($owner, ['manage_self_orders', 'view_self_orders']);
        $this->openShiftFor($owner);

        $order = $this->makeSelfOrder(['status' => SelfOrderStatus::Paid->value, 'processed_by' => $owner->id]);

        $this->actingAs($owner);

        Livewire::test(SelfOrderDashboard::class)
            ->set('activeTab', 'claimed')
            ->assertSee('→ Diproses')
            ->assertDontSee('Bukan pesanan Anda');
    }
}
