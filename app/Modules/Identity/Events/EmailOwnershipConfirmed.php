<?php

namespace App\Modules\Identity\Events;

/**
 * User terbukti memiliki alamat email ini: baru saja memverifikasinya,
 * atau login dengan email yang sudah terverifikasi. Modul lain boleh
 * mempercayai kepemilikan email ini (mis. menyambungkan pesanan guest).
 */
final readonly class EmailOwnershipConfirmed
{
    public function __construct(
        public int $userId,
        public string $email,
    ) {}
}
