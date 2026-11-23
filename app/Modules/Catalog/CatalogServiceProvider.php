<?php

namespace App\Modules\Catalog;

use App\Modules\Catalog\Actions\DeletePackage;
use App\Modules\Catalog\Contracts\PackageCatalog;
use App\Modules\Catalog\Contracts\PackageUsageCheck;
use App\Modules\Catalog\Services\EloquentPackageCatalog;
use App\Modules\Shared\ModuleServiceProvider;
use App\Modules\Shared\Services\ActivityLogger;

class CatalogServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }

    public function register(): void
    {
        $this->app->bind(PackageCatalog::class, EloquentPackageCatalog::class);

        // Pemeriksa "paket sedang dipakai" didaftarkan oleh modul lain dengan tag.
        $this->app->bind(DeletePackage::class, fn ($app) => new DeletePackage(
            $app->make(ActivityLogger::class),
            $app->tagged(PackageUsageCheck::TAG),
        ));
    }
}
