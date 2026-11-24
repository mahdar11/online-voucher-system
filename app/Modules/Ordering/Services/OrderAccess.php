<?php

namespace App\Modules\Ordering\Services;

use App\Modules\Ordering\Models\Order;
use Illuminate\Http\Request;

/**
 * Menentukan siapa yang boleh membuka sebuah pesanan:
 * - user yang login dan memiliki pesanan itu;
 * - browser yang membuat pesanan, membuka link email bertanda tangan,
 *   atau lolos "Cek Pesanan" (disimpan di session);
 * - browser yang membuka magic link "Riwayat" untuk email pesanan itu
 *   (berlaku terbatas).
 */
class OrderAccess
{
    private const SESSION_ORDERS = 'ordering.accessible_orders';

    private const SESSION_EMAIL = 'ordering.verified_email';

    private const MAX_REMEMBERED = 20;

    public function grant(Request $request, Order $order): void
    {
        $ids = $request->session()->get(self::SESSION_ORDERS, []);
        $ids[] = $order->id;

        $request->session()->put(self::SESSION_ORDERS, array_slice(array_values(array_unique($ids)), -self::MAX_REMEMBERED));
    }

    /**
     * Browser ini terbukti memiliki email tersebut (lewat magic link) sampai batas waktu.
     */
    public function grantEmail(Request $request, string $email, int $minutes): void
    {
        $request->session()->put(self::SESSION_EMAIL, [
            'email' => strtolower($email),
            'until' => now()->addMinutes($minutes)->getTimestamp(),
        ]);
    }

    /**
     * Email yang sedang terverifikasi di browser ini, atau null bila tidak ada/kedaluwarsa.
     */
    public function verifiedEmail(Request $request): ?string
    {
        $grant = $request->session()->get(self::SESSION_EMAIL);

        if (! is_array($grant) || ($grant['until'] ?? 0) < now()->getTimestamp()) {
            return null;
        }

        return $grant['email'] ?? null;
    }

    public function canView(Request $request, Order $order): bool
    {
        $userId = $request->user()?->getAuthIdentifier();

        if ($order->user_id !== null && $order->user_id === $userId) {
            return true;
        }

        if (in_array($order->id, $request->session()->get(self::SESSION_ORDERS, []), true)) {
            return true;
        }

        return $this->verifiedEmail($request) === strtolower($order->customer_email);
    }
}
