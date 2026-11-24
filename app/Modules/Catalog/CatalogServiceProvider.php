<?php

namespace App\Modules\Catalog;

use App\Modules\Catalog\Actions\DeletePackage;
use App\Modules\Catalog\Contracts\PackageCatalog;
use App\Modules\Catalog\Contracts\PackageUsageCheck;
use App\Modules\Catalog\Services\EloquentPackageCatalog;
use App\Modules\Shared\ModuleServiceProvider;
use App\Modules\Catalog\Models\VoucherPackage;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Modules\Shared\Services\ActivityLogger;

class CatalogServiceProvider extends ModuleServiceProvider
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
            'voucher_package' => VoucherPackage::class,
        ]);
        $this->app->bind(PackageCatalog::class, EloquentPackageCatalog::class);

        // Pemeriksa "paket sedang dipakai" didaftarkan oleh modul lain dengan tag.
        $this->app->bind(DeletePackage::class, fn ($app) => new DeletePackage(
            $app->make(ActivityLogger::class),
            $app->tagged(PackageUsageCheck::TAG),
        ));
    }
}
