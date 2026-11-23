<?php

namespace App\Modules\Ordering\Http\Controllers;

use App\Modules\Ordering\Models\Order;
use App\Modules\Ordering\Services\OrderAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Cek Pesanan": guest membuka pesanannya dengan nomor invoice + email/no. HP.
 */
class OrderLookupController
{
    private const NOT_FOUND = 'Nomor pesanan dan email/nomor HP tidak cocok.';

    public function create(): Response
    {
        return Inertia::render('Ordering/Lookup');
    }

    public function store(Request $request, OrderAccess $access): RedirectResponse
    {
        $data = $request->validate([
            'invoice_number' => ['required', 'string', 'max:40'],
            'contact' => ['required', 'string', 'max:255'],
        ], [], ['invoice_number' => 'nomor pesanan', 'contact' => 'email atau nomor HP']);

        $order = Order::query()->where('invoice_number', strtoupper(trim($data['invoice_number'])))->first();

        // Pesan gagal selalu sama, agar tidak bisa dipakai menebak invoice yang ada.
        if (! $order || ! $this->contactMatches($order, $data['contact'])) {
            return back()->withErrors(['invoice_number' => self::NOT_FOUND])->onlyInput('invoice_number', 'contact');
        }

        $access->grant($request, $order);

        return to_route('orders.show', $order->invoice_number);
    }

    private function contactMatches(Order $order, string $contact): bool
    {
        $contact = trim($contact);

        if (str_contains($contact, '@')) {
            return hash_equals(strtolower($order->customer_email), strtolower($contact));
        }

        $phone = self::normalizePhone($contact);

        return $phone !== '' && $order->customer_phone !== null
            && hash_equals(self::normalizePhone($order->customer_phone), $phone);
    }

    /**
     * 0812-3456-7890 / +62812... / 62812... -> 812...
     */
    private static function normalizePhone(string $phone): string
    {
        return (string) preg_replace('/^(62|0)/', '', preg_replace('/\D/', '', $phone));
    }
}
