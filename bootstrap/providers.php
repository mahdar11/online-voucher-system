<?php

return [
    App\Providers\AppServiceProvider::class,

    // Modul, diurutkan sesuai arah ketergantungan (yang paling dasar lebih dulu).
    App\Modules\Shared\SharedServiceProvider::class,
    App\Modules\Identity\IdentityServiceProvider::class,
    App\Modules\Catalog\CatalogServiceProvider::class,
    App\Modules\Inventory\InventoryServiceProvider::class,
    App\Modules\Ordering\OrderingServiceProvider::class,
    App\Modules\Payment\PaymentServiceProvider::class,
];
