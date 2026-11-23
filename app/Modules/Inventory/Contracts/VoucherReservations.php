<?php

namespace App\Modules\Inventory\Contracts;

use App\Modules\Inventory\Data\VoucherCode;
use DateTimeInterface;

/**
 * Satu-satunya pintu bagi modul lain untuk memesan (mengunci), melepas,
 * dan menjual voucher. Reservasi ditandai dengan referensi teks milik
 * pemanggil, mis. nomor invoice.
 */
interface VoucherReservations
{
    /**
     * Mengunci sejumlah voucher yang tersedia. Aman untuk checkout bersamaan:
     * voucher yang sedang dikunci transaksi lain dilewati (SKIP LOCKED).
     * Panggil di dalam transaksi database milik pemanggil.
     *
     * @return list<int> id voucher yang dikunci
     *
     * @throws InsufficientStockException
     */
    public function reserve(int $packageId, int $quantity, string $reference, DateTimeInterface $until): array;

    /**
     * Mengembalikan voucher yang masih dikunci oleh referensi ini ke stok.
     *
     * @return int jumlah voucher yang dilepas
     */
    public function release(string $reference): int;

    /**
     * Menandai voucher yang dikunci referensi ini sebagai terjual.
     *
     * @return int jumlah voucher yang terjual
     */
    public function markSold(string $reference, DateTimeInterface $soldAt): int;

    /**
     * Kode voucher yang sudah terjual untuk referensi ini.
     *
     * @return list<VoucherCode>
     */
    public function codesFor(string $reference): array;
}
