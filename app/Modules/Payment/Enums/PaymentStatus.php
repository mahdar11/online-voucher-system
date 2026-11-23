<?php

namespace App\Modules\Payment\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Expired = 'expired';
    case Failed = 'failed';
    /** Dana sudah dikembalikan ke pembeli (ditandai admin). */
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Paid => 'Lunas',
            self::Expired => 'Kedaluwarsa',
            self::Failed => 'Gagal',
            self::Refunded => 'Dikembalikan',
        };
    }
}
