<?php

namespace App\Modules\Ordering\Services;

use App\Modules\Ordering\Models\Order;
use Illuminate\Http\Request;

/**
 * Menentukan siapa yang boleh membuka sebuah pesanan:
 * - user yang login dan memiliki pesanan itu;
 * - browser yang membuat pesanan (disimpan di session), termasuk guest.
 * Akses guest lewat link email ditambahkan di tahap 5.
 */
class OrderAccess
{
    private const SESSION_KEY = 'ordering.accessible_orders';

    private const MAX_REMEMBERED = 20;

    public function grant(Request $request, Order $order): void
    {
        $ids = $request->session()->get(self::SESSION_KEY, []);
        $ids[] = $order->id;

        $request->session()->put(self::SESSION_KEY, array_slice(array_values(array_unique($ids)), -self::MAX_REMEMBERED));
    }

    public function canView(Request $request, Order $order): bool
    {
        $userId = $request->user()?->getAuthIdentifier();

        if ($order->user_id !== null && $order->user_id === $userId) {
            return true;
        }

        return in_array($order->id, $request->session()->get(self::SESSION_KEY, []), true);
    }
}
