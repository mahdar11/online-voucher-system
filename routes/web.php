<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\VoucherBatchController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\VoucherPackageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Home'))->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/akun', fn () => Inertia::render('Account/Dashboard'))
        ->name('account.dashboard');

    Route::middleware('role:'.Role::SuperAdmin->value.'|'.Role::Admin->value)
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/', DashboardController::class)->name('dashboard');

            Route::middleware('permission:'.Permission::ManagePackages->value)
                ->controller(VoucherPackageController::class)
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

            Route::middleware('permission:'.Permission::ManageVouchers->value)->group(function () {
                Route::controller(VoucherController::class)
                    ->prefix('voucher')
                    ->name('vouchers.')
                    ->group(function () {
                        Route::get('/', 'index')->name('index');
                        Route::get('/generate', 'generateForm')->name('generate');
                        Route::post('/generate', 'generate')->name('generate.store');
                        Route::get('/import', 'importForm')->name('import');
                        Route::post('/import', 'import')->name('import.store');
                        Route::get('/import/contoh', 'importSample')->name('import.sample');
                        Route::delete('/{voucher}', 'destroy')->name('destroy');
                    });

                Route::get('/batch', [VoucherBatchController::class, 'index'])->name('batches.index');
            });
        });
});
