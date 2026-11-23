<?php

use App\Enums\Role;
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
            Route::get('/', fn () => Inertia::render('Admin/Dashboard'))->name('dashboard');
        });
});
