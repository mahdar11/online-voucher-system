<?php

namespace App\Modules\Ordering\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Completed = 'completed';
    case Expired = 'expired';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pembayaran',
            self::Paid => 'Dibayar',
            self::Completed => 'Selesai',
            self::Expired => 'Kedaluwarsa',
            self::Failed => 'Gagal',
            self::Refunded => 'Dikembalikan',
        };
    }
}
