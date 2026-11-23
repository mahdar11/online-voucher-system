<?php

use App\Modules\Payment\Http\Controllers\PaymentController;
use App\Modules\Payment\Http\Controllers\XenditWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/pesanan/{invoiceNumber}/bayar', [PaymentController::class, 'start'])
    ->middleware('throttle:10,1')
    ->name('payments.start');

// Dipanggil server Xendit: dikecualikan dari CSRF di bootstrap/app.php.
Route::post('/webhooks/xendit/invoice', [XenditWebhookController::class, 'invoice'])
    ->name('webhooks.xendit.invoice');
