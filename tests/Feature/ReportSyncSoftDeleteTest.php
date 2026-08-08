<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\PaymentSource;
use App\Services\ReportSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regresi untuk audit sinkronisasi main app -> bakso-report setelah fitur soft delete.
 *
 * Bug utama yang dites di sini: SoftDeletes::runSoftDelete() melakukan raw query update
 * (bukan save()), jadi ->delete() pada model soft-deletable TIDAK memicu event 'saved'.
 * SyncsToReport lama hanya mendengarkan 'saved' dan 'deleted', dan secara eksplisit
 * SKIP saat 'deleted' terjadi karena soft delete (dengan asumsi 'saved' sudah menangani —
 * asumsi yang salah). Akibatnya soft delete tidak pernah tersalin ke report sama sekali.
 *
 * Koneksi 'mysql_report' dialihkan ke sqlite in-memory terpisah khusus untuk test ini,
 * dengan skema minimal yang merepresentasikan tabel report setelah migration
 * 2026_08_08_00000{1,2,4,5,6} di project bakso-report. Test ini TIDAK menyentuh database
 * report yang sesungguhnya.
 */
class ReportSyncSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.mysql_report', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        DB::purge('mysql_report');

        Schema::connection('mysql_report')->create('categories', function ($table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('target_kitchen', 20)->default('food');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->index('slug');
        });

        Schema::connection('mysql_report')->create('payment_sources', function ($table) {
            $table->id();
            $table->string('name', 100);
            $table->string('type', 20);
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_active_pos')->default(true);
            $table->boolean('is_active_self_order')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::connection('mysql_report')->create('modifier_groups', function ($table) {
            $table->id();
            $table->string('name', 100);
            $table->string('selection_type', 20)->default('single');
            $table->boolean('is_required')->default(false);
            $table->integer('min_selections')->default(0);
            $table->integer('max_selections')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::connection('mysql_report')->create('modifiers', function ($table) {
            $table->id();
            $table->unsignedBigInteger('modifier_group_id');
            $table->unsignedBigInteger('component_id')->nullable();
            $table->string('name', 100);
            $table->decimal('price_adjustment', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('mysql_report');
        parent::tearDown();
    }

    private function reportRow(string $table, int $id): ?object
    {
        return DB::connection('mysql_report')->table($table)->where('id', $id)->first();
    }

    // -----------------------------------------------------------------------
    // Generic trait: create / update / soft delete / restore
    // -----------------------------------------------------------------------

    public function test_create_syncs_row_with_null_deleted_at(): void
    {
        $category = Category::create([
            'name' => 'Bakso', 'slug' => 'bakso', 'sort_order' => 1, 'is_active' => true,
        ]);

        $row = $this->reportRow('categories', $category->id);

        $this->assertNotNull($row, 'Baris tidak tersalin ke report saat create.');
        $this->assertNull($row->deleted_at);
        $this->assertSame('Bakso', $row->name);
    }

    public function test_update_syncs_changed_fields(): void
    {
        $category = Category::create([
            'name' => 'Bakso', 'slug' => 'bakso', 'sort_order' => 1, 'is_active' => true,
        ]);

        $category->update(['name' => 'Bakso Malang']);

        $row = $this->reportRow('categories', $category->id);
        $this->assertSame('Bakso Malang', $row->name);
    }

    public function test_soft_delete_syncs_deleted_at_to_report(): void
    {
        $category = Category::create([
            'name' => 'Bakso', 'slug' => 'bakso', 'sort_order' => 1, 'is_active' => true,
        ]);

        $category->delete();

        $row = $this->reportRow('categories', $category->id);

        $this->assertNotNull($row, 'Baris harus TETAP ADA di report (bukan hard delete).');
        $this->assertNotNull(
            $row->deleted_at,
            'deleted_at harus tersalin ke report saat soft delete pada main app.'
        );
    }

    public function test_restore_clears_deleted_at_in_report(): void
    {
        $category = Category::create([
            'name' => 'Bakso', 'slug' => 'bakso', 'sort_order' => 1, 'is_active' => true,
        ]);
        $category->delete();
        $this->assertNotNull($this->reportRow('categories', $category->id)->deleted_at);

        $category->restore();

        $row = $this->reportRow('categories', $category->id);
        $this->assertNull($row->deleted_at, 'deleted_at harus kembali NULL di report setelah restore().');
    }

    public function test_soft_deleting_user_model_also_syncs_via_trait(): void
    {
        // User punya trait yang sama; pastikan fix di SyncsToReport berlaku generik,
        // bukan cuma untuk Category.
        $ps = PaymentSource::create([
            'name' => 'Cash', 'type' => 'cash', 'is_active_pos' => true, 'is_active_self_order' => true,
        ]);

        $ps->delete();

        $row = $this->reportRow('payment_sources', $ps->id);
        $this->assertNotNull($row->deleted_at);
    }

    // -----------------------------------------------------------------------
    // Bug pre-existing: payment_sources.is_active mapping
    // -----------------------------------------------------------------------

    public function test_payment_source_sync_maps_pos_and_self_order_flags_correctly(): void
    {
        $ps = PaymentSource::create([
            'name' => 'QRIS', 'type' => 'qris',
            'is_active_pos' => true, 'is_active_self_order' => false,
        ]);

        $row = $this->reportRow('payment_sources', $ps->id);

        $this->assertSame(1, (int) $row->is_active_pos);
        $this->assertSame(0, (int) $row->is_active_self_order);
        // Kolom lama 'is_active': true jika salah satu channel aktif.
        $this->assertSame(1, (int) $row->is_active);
    }

    public function test_report_sync_service_public_sync_payment_source_includes_deleted_at(): void
    {
        $ps = PaymentSource::create([
            'name' => 'Transfer', 'type' => 'qris', 'is_active_pos' => true, 'is_active_self_order' => true,
        ]);
        $ps->delete();
        $ps->refresh();

        app(ReportSyncService::class)->syncPaymentSource($ps);

        $row = $this->reportRow('payment_sources', $ps->id);
        $this->assertNotNull($row->deleted_at);
    }

    // -----------------------------------------------------------------------
    // ModifierGroup -> Modifier cascade (fix: per-model loop, bukan bulk relation delete)
    // -----------------------------------------------------------------------

    public function test_soft_deleting_modifier_group_cascades_and_syncs_child_modifiers(): void
    {
        $group = ModifierGroup::create(['name' => 'Level Pedas', 'selection_type' => 'single']);
        $mod1 = $group->modifiers()->create(['name' => 'Pedas', 'price_adjustment' => 0]);
        $mod2 = $group->modifiers()->create(['name' => 'Tidak Pedas', 'price_adjustment' => 0]);

        $group->delete();

        $this->assertNotNull($this->reportRow('modifier_groups', $group->id)->deleted_at);
        $this->assertNotNull(
            $this->reportRow('modifiers', $mod1->id)->deleted_at,
            'Modifier anak harus ikut tersinkron ke report saat grupnya di-soft-delete.'
        );
        $this->assertNotNull($this->reportRow('modifiers', $mod2->id)->deleted_at);
    }

    public function test_restoring_modifier_group_cascades_and_syncs_child_modifiers(): void
    {
        $group = ModifierGroup::create(['name' => 'Level Pedas', 'selection_type' => 'single']);
        $mod = $group->modifiers()->create(['name' => 'Pedas', 'price_adjustment' => 0]);
        $group->delete();

        $group->restore();

        $this->assertNull($this->reportRow('modifier_groups', $group->id)->deleted_at);
        $this->assertNull(
            $this->reportRow('modifiers', $mod->id)->deleted_at,
            'Modifier anak harus ikut ter-restore ke report saat grupnya di-restore.'
        );
    }
}
