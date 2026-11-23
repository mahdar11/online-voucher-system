<x-mail::message>
# Pesanan Anda sudah dibuat

Halo {{ $order->customer_name }},

Terima kasih sudah memesan. Voucher sudah kami simpan untuk Anda sampai
**{{ $order->expires_at->timezone('Asia/Jakarta')->translatedFormat('d F Y, H.i') }} WIB**.
Selesaikan pembayaran sebelum batas waktu tersebut.

<x-mail::table>
| Paket | Jumlah | Subtotal |
|:------|:------:|---------:|
@foreach ($items as $item)
| {{ $item['name'] }} | {{ $item['quantity'] }} | Rp{{ number_format($item['total'], 0, ',', '.') }} |
@endforeach
| **Total** | | **Rp{{ number_format($order->total, 0, ',', '.') }}** |
</x-mail::table>

<x-mail::button :url="$orderUrl">
Lanjutkan Pembayaran
</x-mail::button>

Nomor pesanan: **{{ $order->invoice_number }}**

Jika batas waktu terlewat, pesanan otomatis dibatalkan dan Anda bisa memesan lagi.

Salam,<br>
{{ config('app.name') }}
</x-mail::message>
