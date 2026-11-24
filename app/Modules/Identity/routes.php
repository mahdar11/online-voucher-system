<?php

use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Http\Controllers\AccountController;
use App\Modules\Identity\Http\Controllers\Admin\UserController;
use App\Modules\Identity\Http\Controllers\InvitationController;
use Illuminate\Support\Facades\Route;

// Route login, register, reset password, dan verifikasi email didaftarkan oleh Fortify.

Route::middleware('auth')->group(function () {
    Route::get('/akun', [AccountController::class, 'dashboard'])->name('account.dashboard');
});

// Undangan staf: link bertanda tangan dari email.
Route::middleware('signed')->group(function () {
    Route::get('/undangan/{user}', [InvitationController::class, 'show'])->name('invitation.show');
    Route::post('/undangan/{user}', [InvitationController::class, 'store'])->name('invitation.store');
});

Route::admin(function () {
    Route::middleware(Permission::ManageUsers->middleware())
        ->controller(UserController::class)
        ->prefix('pengguna')
        ->name('users.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/tambah', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{user}/ubah', 'edit')->name('edit');
            Route::put('/{user}', 'update')->name('update');
            Route::patch('/{user}/status', 'toggle')->name('toggle');
            Route::post('/{user}/undangan', 'resendInvitation')->name('invitation');
        });
});
