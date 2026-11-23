<?php

namespace App\Providers;

use Illuminate\Foundation\DevCommands;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // `composer dev` juga menjalankan scheduler, agar pesanan yang lewat
        // batas bayar otomatis kedaluwarsa (orders:expire setiap menit).
        DevCommands::artisan('schedule:work', 'scheduler');
    }
}
