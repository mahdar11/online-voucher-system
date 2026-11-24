<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kolom polymorphic (model_type / subject_type) sebelumnya menyimpan nama class
 * lengkap. Setelah refactor modular monolith, User pindah namespace sehingga
 * role user lama tidak terbaca lagi. Sekarang semua model memakai alias tetap
 * (morph map) — migration ini mengonversi data lama ke alias tersebut.
 */
return new class extends Migration
{
    /**
     * alias => nama class lama yang mungkin tersimpan
     *
     * @var array<string, list<string>>
     */
    private const ALIASES = [
        'user' => ['App\Models\User', 'App\Modules\Identity\Models\User'],
        'voucher_package' => ['App\Models\VoucherPackage', 'App\Modules\Catalog\Models\VoucherPackage'],
        'voucher_batch' => ['App\Models\VoucherBatch', 'App\Modules\Inventory\Models\VoucherBatch'],
        'voucher' => ['App\Models\Voucher', 'App\Modules\Inventory\Models\Voucher'],
        'order' => ['App\Models\Order', 'App\Modules\Ordering\Models\Order'],
        'payment' => ['App\Models\Payment', 'App\Modules\Payment\Models\Payment'],
    ];

    private const COLUMNS = [
        'model_has_roles' => 'model_type',
        'model_has_permissions' => 'model_type',
        'activity_logs' => 'subject_type',
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $column) {
            foreach (self::ALIASES as $alias => $classes) {
                DB::table($table)->whereIn($column, $classes)->update([$column => $alias]);
            }
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $column) {
            foreach (self::ALIASES as $alias => $classes) {
                DB::table($table)->where($column, $alias)->update([$column => end($classes)]);
            }
        }
    }
};
