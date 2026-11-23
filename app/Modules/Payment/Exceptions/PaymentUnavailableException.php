<?php

namespace App\Modules\Payment\Exceptions;

use RuntimeException;

/**
 * Pembayaran tidak bisa dimulai; pesannya aman ditampilkan ke pembeli.
 */
class PaymentUnavailableException extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self('Pembayaran online belum tersedia. Silakan coba lagi nanti.');
    }

    public static function notPayable(): self
    {
        return new self('Pesanan ini tidak bisa dibayar lagi.');
    }

    public static function tooLate(): self
    {
        return new self('Waktu pembayaran hampir habis. Silakan buat pesanan baru.');
    }

    public static function gatewayError(): self
    {
        return new self('Gagal membuat tagihan pembayaran. Silakan coba lagi sebentar lagi.');
    }
}
