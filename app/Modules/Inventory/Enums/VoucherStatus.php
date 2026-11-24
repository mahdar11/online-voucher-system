<?php

namespace App\Modules\Inventory\Enums;

enum VoucherStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Used = 'used';
    case Expired = 'expired';
    /** Ditarik dari penjualan online, mis. kartu fisiknya ternyata sudah terjual di toko. */
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Reserved => 'Dipesan',
            self::Sold => 'Terjual',
            self::Used => 'Terpakai',
            self::Expired => 'Kedaluwarsa',
            self::Withdrawn => 'Ditarik',
        };
    }
}
