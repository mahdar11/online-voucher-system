<?php

return [

    /*
    | Lama voucher di-reserve saat checkout sebelum dilepas kembali ke stok
    | jika pembayaran tidak diselesaikan.
    */
    'reservation_minutes' => (int) env('VOUCHER_RESERVATION_MINUTES', 15),

    /*
    | Zona waktu bisnis untuk batas hari di laporan dan tampilan admin.
    | Data tetap disimpan dalam UTC.
    */
    'business_timezone' => env('BUSINESS_TIMEZONE', 'Asia/Jakarta'),

    /*
    | Akun super admin awal yang dibuat oleh SuperAdminSeeder.
    */
    'super_admin' => [
        'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
        'email' => env('SUPER_ADMIN_EMAIL'),
        'password' => env('SUPER_ADMIN_PASSWORD'),
    ],

    /*
    | Akun demo untuk mencoba role admin dan customer. Hanya dibuat oleh
    | DemoUserSeeder saat APP_ENV=local.
    */
    'demo_users' => [
        'admin' => [
            'name' => 'Admin Demo',
            'email' => env('DEMO_ADMIN_EMAIL', 'admin@online-voucher.test'),
            'password' => env('DEMO_ADMIN_PASSWORD'),
        ],
        'customer' => [
            'name' => 'Customer Demo',
            'email' => env('DEMO_CUSTOMER_EMAIL', 'customer@online-voucher.test'),
            'password' => env('DEMO_CUSTOMER_PASSWORD'),
        ],
    ],

];
