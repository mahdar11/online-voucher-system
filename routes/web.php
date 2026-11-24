<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Modules\Identity\Enums\Permission;
use Illuminate\Support\Facades\Route;

/*
| Route milik modul ada di app/Modules/{Modul}/routes.php.
| File ini hanya berisi halaman lapisan aplikasi yang menggabungkan beberapa modul.
*/

Route::admin(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::middleware(Permission::ViewReports->middleware())->group(function () {
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/export', [ReportController::class, 'export'])->name('reports.export');
    });

    Route::middleware(Permission::ManageSettings->middleware())->group(function () {
        Route::get('/pengaturan', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/pengaturan', [SettingsController::class, 'update'])->name('settings.update');
        Route::get('/log', [ActivityLogController::class, 'index'])->name('activity.index');
    });
});
