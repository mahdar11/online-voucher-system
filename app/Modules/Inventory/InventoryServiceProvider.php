<?php

namespace App\Modules\Inventory;

use App\Modules\Catalog\Contracts\PackageStockReader;
use App\Modules\Catalog\Contracts\PackageUsageCheck;
use App\Modules\Inventory\Contracts\VoucherReservations;
use App\Modules\Inventory\Services\EloquentVoucherReservations;
use App\Modules\Inventory\Services\PackageHasVouchers;
use App\Modules\Inventory\Services\StockCounter;
use App\Modules\Shared\ModuleServiceProvider;
use App\Modules\Inventory\Models\Voucher;
use App\Modules\Inventory\Models\VoucherBatch;
use Illuminate\Database\Eloquent\Relations\Relation;

class InventoryServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }

    public function register(): void
    {
        // Alias tetap untuk kolom polymorphic (bukan nama class), agar data tidak
        // rusak bila class dipindah. Lihat migration normalize_morph_types.
        Relation::enforceMorphMap([
            'voucher' => Voucher::class,
            'voucher_batch' => VoucherBatch::class,
        ]);
        // Inventory menyediakan implementasi untuk port milik Catalog.
        $this->app->bind(PackageStockReader::class, StockCounter::class);
        $this->app->bind(VoucherReservations::class, EloquentVoucherReservations::class);
        $this->app->tag([PackageHasVouchers::class], PackageUsageCheck::TAG);
    }
}
