<?php

use App\Modules\Identity\Enums\Permission;
use App\Modules\Ordering\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Modules\Ordering\Http\Controllers\CheckoutController;
use App\Modules\Ordering\Http\Controllers\MyOrdersController;
use App\Modules\Ordering\Http\Controllers\OrderController;
use App\Modules\Ordering\Http\Controllers\OrderHistoryController;
use App\Modules\Ordering\Http\Controllers\OrderLookupController;
use Illuminate\Support\Facades\Route;

Route::get('/beli/{slug}', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/beli/{slug}', [CheckoutController::class, 'store'])
    ->middleware('throttle:checkout')
    ->name('checkout.store');

Route::get('/pesanan/{order:invoice_number}', [OrderController::class, 'show'])->name('orders.show');
Route::post('/pesanan/{order:invoice_number}/batal', [OrderController::class, 'cancel'])->name('orders.cancel');

// Guest: buka pesanan dengan nomor invoice + email/no. HP.
Route::get('/cek-pesanan', [OrderLookupController::class, 'create'])->name('orders.lookup.create');
Route::post('/cek-pesanan', [OrderLookupController::class, 'store'])
    ->middleware('throttle:order-lookup')
    ->name('orders.lookup.store');

// Guest: riwayat pembelian lewat magic link email.
Route::get('/riwayat', [OrderHistoryController::class, 'create'])->name('orders.history.create');
Route::post('/riwayat', [OrderHistoryController::class, 'store'])->name('orders.history.store');
Route::get('/riwayat/pesanan', [OrderHistoryController::class, 'index'])->name('orders.history.index');
Route::get('/riwayat/buka/{token}', [OrderHistoryController::class, 'open'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->name('orders.history.open');

Route::middleware('auth')->group(function () {
    Route::get('/akun/pesanan', [MyOrdersController::class, 'index'])->name('account.orders');
});

Route::admin(function () {
    Route::middleware(Permission::ManageOrders->middleware())
        ->controller(AdminOrderController::class)
        ->prefix('pesanan')
        ->name('orders.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{order:invoice_number}', 'show')->name('show');
            Route::post('/{order:invoice_number}/kirim-ulang', 'resend')->name('resend');
            Route::post('/{order:invoice_number}/refund', 'refund')->name('refund');
            Route::post('/{order:invoice_number}/batal', 'cancel')->name('cancel');
        });
});
