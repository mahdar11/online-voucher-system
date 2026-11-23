<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

/*
| Route milik modul ada di app/Modules/{Modul}/routes.php.
| File ini hanya berisi halaman lapisan aplikasi yang menggabungkan beberapa modul.
*/

Route::admin(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
});
