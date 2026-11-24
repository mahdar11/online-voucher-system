<?php

use App\Modules\Catalog\Http\Controllers\Admin\PackageController;
use App\Modules\Catalog\Http\Controllers\StorefrontController;
use App\Modules\Identity\Enums\Permission;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'home'])->name('home');

Route::admin(function () {
    Route::middleware(Permission::ManagePackages->middleware())
        ->controller(PackageController::class)
        ->prefix('paket')
        ->name('packages.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/tambah', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{package}/ubah', 'edit')->name('edit');
            Route::put('/{package}', 'update')->name('update');
            Route::patch('/{package}/status', 'toggle')->name('toggle');
            Route::delete('/{package}', 'destroy')->name('destroy');
        });
});
