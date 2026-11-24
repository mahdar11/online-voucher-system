<?php

namespace Tests\Architecture;

use App\Modules\Catalog\Models\VoucherPackage;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Inventory\Models\Voucher;
use App\Modules\Inventory\Models\VoucherBatch;
use App\Modules\Ordering\Models\Order;
use App\Modules\Payment\Models\Payment;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kolom polymorphic harus menyimpan alias tetap, bukan nama class. Kalau tidak,
 * memindahkan class (seperti saat refactor modul) membuat role user hilang.
 */
class MorphMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_use_stable_morph_aliases(): void
    {
        $this->assertSame('user', (new User)->getMorphClass());
        $this->assertSame('voucher_package', (new VoucherPackage)->getMorphClass());
        $this->assertSame('voucher', (new Voucher)->getMorphClass());
        $this->assertSame('voucher_batch', (new VoucherBatch)->getMorphClass());
        $this->assertSame('order', (new Order)->getMorphClass());
        $this->assertSame('payment', (new Payment)->getMorphClass());
    }

    public function test_role_assignments_are_stored_with_alias(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(Role::Admin->value);

        $this->assertSame(['user'], DB::table('model_has_roles')->distinct()->pluck('model_type')->all());
    }

    public function test_migration_restores_roles_saved_under_old_class_names(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(Role::Admin->value);

        // Simulasikan data dari sebelum refactor.
        DB::table('model_has_roles')->update(['model_type' => 'App\Models\User']);
        $this->assertFalse($user->fresh()->hasRole(Role::Admin->value));

        (require database_path('migrations/2026_11_23_130000_normalize_morph_types.php'))->up();

        $this->assertTrue($user->fresh()->hasRole(Role::Admin->value));
    }
}
