<x-mail::message>
# Pembayaran Anda akan dikembalikan

Halo {{ $order->customer_name }},

Pembayaran untuk pesanan **{{ $order->invoice_number }}**
(Rp{{ number_format($order->total, 0, ',', '.') }}) kami terima setelah batas waktu pembayaran,
dan sayangnya stok voucher sudah habis.

Dana Anda akan **dikembalikan penuh**. Tim kami akan menghubungi Anda melalui email ini
untuk proses pengembalian. Mohon maaf atas ketidaknyamanannya.

<x-mail::button :url="$orderUrl">
Lihat Pesanan
</x-mail::button>

Salam,<br>
{{ config('app.name') }}
</x-mail::message>
