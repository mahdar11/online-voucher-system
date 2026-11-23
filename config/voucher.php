<?php

return [

    /*
    | Lama voucher di-reserve saat checkout sebelum dilepas kembali ke stok
    | jika pembayaran tidak diselesaikan.
    */
    'reservation_minutes' => (int) env('VOUCHER_RESERVATION_MINUTES', 15),

    /*
    | Akun super admin awal yang dibuat oleh SuperAdminSeeder.
    */
    'super_admin' => [
        'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
        'email' => env('SUPER_ADMIN_EMAIL'),
        'password' => env('SUPER_ADMIN_PASSWORD'),
    ],

];
