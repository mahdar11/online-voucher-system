<?php

use App\Modules\Ordering\Http\Controllers\CheckoutController;
use App\Modules\Ordering\Http\Controllers\MyOrdersController;
use App\Modules\Ordering\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/beli/{slug}', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/beli/{slug}', [CheckoutController::class, 'store'])
    ->middleware('throttle:checkout')
    ->name('checkout.store');

Route::get('/pesanan/{order:invoice_number}', [OrderController::class, 'show'])->name('orders.show');
Route::post('/pesanan/{order:invoice_number}/batal', [OrderController::class, 'cancel'])->name('orders.cancel');

Route::middleware('auth')->group(function () {
    Route::get('/akun/pesanan', [MyOrdersController::class, 'index'])->name('account.orders');
});
