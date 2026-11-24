<?php

use App\Modules\Identity\Enums\Permission;
use App\Modules\Inventory\Http\Controllers\Admin\BatchController;
use App\Modules\Inventory\Http\Controllers\Admin\GenerateVoucherController;
use App\Modules\Inventory\Http\Controllers\Admin\ImportVoucherController;
use App\Modules\Inventory\Http\Controllers\Admin\VoucherController;
use Illuminate\Support\Facades\Route;

Route::admin(function () {
    Route::middleware(Permission::ManageVouchers->middleware())->group(function () {
        Route::prefix('voucher')->name('vouchers.')->group(function () {
            Route::get('/', [VoucherController::class, 'index'])->name('index');

            Route::get('/generate', [GenerateVoucherController::class, 'create'])->name('generate');
            Route::post('/generate', [GenerateVoucherController::class, 'store'])->name('generate.store');

            Route::get('/import', [ImportVoucherController::class, 'create'])->name('import');
            Route::post('/import', [ImportVoucherController::class, 'store'])->name('import.store');
            Route::get('/import/contoh', [ImportVoucherController::class, 'sample'])->name('import.sample');

            Route::patch('/{voucher}/tarik', [VoucherController::class, 'withdraw'])->name('withdraw');
            Route::delete('/{voucher}', [VoucherController::class, 'destroy'])->name('destroy');
        });

        Route::get('/batch', [BatchController::class, 'index'])->name('batches.index');
    });
});
