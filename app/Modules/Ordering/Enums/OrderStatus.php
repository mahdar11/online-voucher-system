<?php

namespace App\Modules\Ordering\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Failed = 'failed';
    /** Dibayar setelah kedaluwarsa dan stok sudah habis: admin perlu mengembalikan dana. */
    case RefundRequired = 'refund_required';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pembayaran',
            self::Paid => 'Lunas',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
            self::Expired => 'Kedaluwarsa',
            self::Failed => 'Gagal',
            self::RefundRequired => 'Perlu Refund',
            self::Refunded => 'Dikembalikan',
        };
    }

    /**
     * Pembayaran sudah diterima dan voucher sudah menjadi milik pembeli.
     */
    public function hasVouchers(): bool
    {
        return in_array($this, [self::Paid, self::Completed], true);
    }
}
