<?php

namespace App\Modules\Payment\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Expired = 'expired';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Paid => 'Lunas',
            self::Expired => 'Kedaluwarsa',
            self::Failed => 'Gagal',
        };
    }
}
