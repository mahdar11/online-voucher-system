<?php

namespace App\Modules\Shared;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Dasar untuk service provider setiap modul: memuat migration dan route
 * dari folder modul itu sendiri.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Folder modul (biasanya __DIR__ dari provider turunan).
     */
    abstract protected function modulePath(): string;

    public function boot(): void
    {
        $this->bootModule();

        $migrations = $this->modulePath().'/Database/Migrations';
        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }

        // Route dimuat setelah semua provider boot, supaya macro seperti
        // Route::admin() dari modul lain sudah terdaftar.
        $routes = $this->modulePath().'/routes.php';
        if (is_file($routes)) {
            $this->app->booted(function () use ($routes) {
                if (! $this->app->routesAreCached()) {
                    Route::middleware('web')->group($routes);
                }
            });
        }
    }

    protected function bootModule(): void {}
}
