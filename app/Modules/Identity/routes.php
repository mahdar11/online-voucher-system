<?php

use App\Modules\Identity\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

// Route login, register, dan reset password didaftarkan oleh Fortify.

Route::middleware('auth')->group(function () {
    Route::get('/akun', [AccountController::class, 'dashboard'])->name('account.dashboard');
});
