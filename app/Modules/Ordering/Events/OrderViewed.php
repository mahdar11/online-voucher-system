<?php

namespace App\Modules\Ordering\Events;

/**
 * Pemilik membuka halaman pesanannya. Modul lain (mis. Payment) boleh
 * bereaksi, contohnya mengecek status pembayaran terbaru, sebelum
 * halaman dirender.
 */
final readonly class OrderViewed
{
    public function __construct(public string $invoiceNumber) {}
}
