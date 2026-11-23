<x-mail::message>
# Pembayaran diterima 🎉

Halo {{ $order->customer_name }},

Pembayaran pesanan **{{ $order->invoice_number }}** sudah kami terima. Berikut kode voucher Anda:

<x-mail::table>
| # | Kode | Password |
|:-:|:-----|:---------|
@foreach ($codes as $i => $code)
| {{ $i + 1 }} | **{{ $code['code'] }}** | {{ $code['password'] ?? '-' }} |
@endforeach
</x-mail::table>

@foreach ($items as $item)
**{{ $item['name'] }}** × {{ $item['quantity'] }}
@if ($item['duration'])
<br>Durasi: {{ $item['duration'] }} · Masa aktif: {{ $item['validity'] }}
@endif

@endforeach

**Cara pakai:** sambungkan perangkat ke WiFi hotspot, buka halaman login yang muncul,
lalu masukkan kode (dan password jika ada). Masa aktif mulai dihitung sejak login pertama.

<x-mail::button :url="$orderUrl">
Lihat Pesanan
</x-mail::button>

Simpan email ini. Jangan bagikan kode voucher kepada orang lain.

Salam,<br>
{{ config('app.name') }}
</x-mail::message>
