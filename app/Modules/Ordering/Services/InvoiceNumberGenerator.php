<?php

namespace App\Modules\Ordering\Services;

use App\Modules\Ordering\Models\Order;

/**
 * Nomor invoice seperti INV-20261123-7KQ2MZ: tanggal + 6 karakter acak
 * (tanpa karakter yang mudah tertukar), dijamin unik.
 */
class InvoiceNumberGenerator
{
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const RANDOM_LENGTH = 6;

    public function next(): string
    {
        do {
            $number = 'INV-'.now()->format('Ymd').'-'.$this->random();
        } while (Order::query()->where('invoice_number', $number)->exists());

        return $number;
    }

    private function random(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $result = '';

        for ($i = 0; $i < self::RANDOM_LENGTH; $i++) {
            $result .= self::ALPHABET[random_int(0, $max)];
        }

        return $result;
    }
}
